<?php

namespace App\Util;

use Exception;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ServerUtil
 *
 * Util for server administration functionality
 *
 * @package App\Util
 */
class ServerUtil
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Get host system uptime
     *
     * @return string The formatted host uptime
     */
    public function getHostUptime(): string
    {
        // get host uptime
        $uptimeString = file_get_contents('/proc/uptime');
        if ($uptimeString === false) {
            return 'error: Unable to read uptime.';
        }

        $uptime = (int) strtok($uptimeString, '.');

        // get uptime values
        $days = floor($uptime / (3600 * 24));
        $hours = floor(($uptime % (3600 * 24)) / 3600);
        $minutes = floor(($uptime % 3600) / 60);

        // format output
        return sprintf('Days: %02d, Hours: %02d, Min: %02d', $days, $hours, $minutes);
    }

    /**
     * Get CPU usage percentage
     *
     * @return float The CPU usage percentage
     */
    public function getCpuUsage(): float
    {
        $load = 0;
        $loads = sys_getloadavg();

        // check if sys_getloadavg() returned an array and has at least one value
        if (!is_array($loads) || count($loads) < 1) {
            return $load;
        }

        // get number of CPU cores
        $coreNums = shell_exec('nproc');

        // check if nproc command returned a valid number of cores
        if (!$coreNums) {
            return 0;
        }

        // fetch number of CPU cores using nproc command
        $coreNums = (int) trim($coreNums);

        // validate number of cores obtained
        if ($coreNums > 0) {
            // calculate CPU usage in percentage
            $load = round(min($loads[0] / $coreNums * 100, 100), 2);
        }

        return max($load, 0.1);
    }

    /**
     * Get RAM usage information
     *
     * @return array<string,string> Array containing RAM usage information
     */
    public function getRamUsage(): array
    {
        $memoryRaw = file_get_contents('/proc/meminfo');
        $memoryTotal = 0;
        $memoryAvailable = 0;

        if ($memoryRaw !== false) {
            $lines = explode("\n", $memoryRaw);
            foreach ($lines as $line) {
                if (str_contains($line, 'MemTotal:')) {
                    $memoryTotal = (float) filter_var($line, FILTER_SANITIZE_NUMBER_INT) / 1048576;
                } elseif (str_contains($line, 'MemAvailable:')) {
                    $memoryAvailable = (float) filter_var($line, FILTER_SANITIZE_NUMBER_INT) / 1048576;
                }
            }
        }

        // calculate memory usage
        $memoryUsed = $memoryTotal - $memoryAvailable;

        return [
            'used'  => number_format($memoryUsed, 2),
            'free'  => number_format($memoryAvailable, 2),
            'total' => number_format($memoryTotal, 2)
        ];
    }

    /**
     * Get RAM usage percentage
     *
     * @return int The RAM usage percentage
     */
    public function getRamUsagePercentage(): int
    {
        $ramUsageData = $this->getRamUsage();

        // calculate percentage
        $usage = (int) (((float) $ramUsageData['used'] / (float) $ramUsageData['total']) * 100);

        return $usage;
    }

    /**
     * Get storage usage
     *
     * @return int|null The storage usage
     */
    public function getStorageUsage(): ?int
    {
        try {
            $storageUsage = disk_total_space('/') - disk_free_space('/');
            return (int) ($storageUsage / 1073741824); // convert bytes to GB
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting storage usage: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get drive usage in percentage
     *
     * @return string|null The drive usage percentage or null on error
     */
    public function getDriveUsagePercentage(): ?string
    {
        try {
            $totalSpace = disk_total_space('/');
            $freeSpace = disk_free_space('/');
            $usedSpace = $totalSpace - $freeSpace;

            if ($totalSpace > 0) {
                $usagePercentage = ($usedSpace / $totalSpace) * 100;
                return (string) (int) number_format($usagePercentage, 2); // format to 2 decimal places
            } else {
                return null;
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting drive usage percentage: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get web username
     *
     * @return string|null The web username or null on error
     */
    public function getWebUsername(): ?string
    {
        try {
            return (string) exec('whoami');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get web username ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if host system is linux
     *
     * @return bool True if the system is running Linux, false otherwise
     */
    public function isSystemLinux(): bool
    {
        // check if system is linux
        if (strtolower(substr(PHP_OS, 0, 3)) == 'lin') {
            return true;
        }

        return false;
    }

    /**
     * Check if web user has sudo privileges
     *
     * @return bool True if the web user has sudo privileges, false otherwise
     */
    public function isWebUserSudo(): bool
    {
        // testing sudo exec
        $exec = (string) exec('sudo echo test');

        // count output length
        $len = strlen($exec);

        // check if length is valid
        if ($len == 4) {
            return true;
        }

        return false;
    }

    /**
     * Get information about installed software packages and Linux distribution
     *
     * @return array<mixed> Array containing information about installed software packages and the Linux distribution
     */
    public function getSystemInfo(): array
    {
        $distro = [];

        // get kernel version and architecture
        $kernelInfo = php_uname('r') . ' ' . php_uname('m');
        $distro['kernel_version'] = php_uname('s') . ' ' . $kernelInfo;
        $distro['kernel_arch'] = php_uname('m');

        // get distribution name
        $releaseFiles = glob('/etc/*-release');
        $distroName = '';

        // check if release files exist
        if (!is_iterable($releaseFiles)) {
            return $distro;
        }

        foreach ($releaseFiles as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $distroName .= $content;
            }
        }
        $distro['operating_system'] = trim($distroName);

        return $distro;
    }

    /**
     * Get system installation date using shell_exec
     *
     * @return string The system installation date
     */
    public function getSystemInstallInfo(): string
    {
        $command = "sudo tune2fs -l $(df / | tail -1 | awk '{print $1}') | grep 'Filesystem created'";
        $output = shell_exec($command);

        // check if output is empty
        if (!$output) {
            return "Unable to retrieve installation date.";
        }

        // get installation date from output
        if (preg_match('/Filesystem created:\s*(.+)/', $output, $matches)) {
            $installDate = strtotime($matches[1]);
            if (!$installDate) {
                return "Unable to parse installation date.";
            }
            $formattedDate = date('Y-m-d', $installDate);

            // calculate days ago
            $currentTime = time();
            $daysAgo = floor(($currentTime - $installDate) / (60 * 60 * 24));

            return $formattedDate . " (" . $daysAgo . " days ago)";
        }

        return "Installation date not found in command output.";
    }

    /**
     * Check if service or php extension is installed
     *
     * @param string $serviceName The name of service
     *
     * @return bool The service is installed, false otherwise
     */
    public function isServiceInstalled(string $serviceName): bool
    {
        static $installedPackages = null;

        // check if composer is installed
        if ($serviceName == strtolower('composer')) {
            $composerPath = shell_exec('which composer');
            if ($composerPath != null) {
                return true;
            }
        }

        // get list of installed dpkg packages
        if ($installedPackages === null) {
            $output = shell_exec('dpkg -l');
            if ($output != null) {
                $installedPackages = explode("\n", $output);
            }
        }

        // check dpkg package
        foreach ($installedPackages as $package) {
            if (stripos($package, $serviceName) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if PHP extension is installed
     *
     * @param string $extension The name of PHP extension
     *
     * @return bool True if the PHP extension is installed, false otherwise
     */
    public function isPhpExtensionInstalled(string $extension): bool
    {
        return extension_loaded($extension);
    }

    /**
     * Get network statistics
     *
     * @param string $interface The network interface
     * @param string $pingToIp The IP address to ping (default: 8.8.8.8)
     *
     * @return array<string,float|string> The network statistics
     */
    public function getNetworkStats(string $interface = 'enp0s6', string $pingToIp = '8.8.8.8'): array
    {
        $maxSpeedMbps = (int) $this->appUtil->getEnvValue('NETWORK_SPEED_MAX');

        // first measurement
        $rx1 = shell_exec("cat /proc/net/dev | awk '/$interface/ {print $2}'");
        $tx1 = shell_exec("cat /proc/net/dev | awk '/$interface/ {print $10}'");
        $rx1 = intval($rx1);
        $tx1 = intval($tx1);

        // wait 1 second before second measurement
        usleep(1000000);

        // second measurement
        $rx2 = shell_exec("cat /proc/net/dev | awk '/$interface/ {print $2}'");
        $tx2 = shell_exec("cat /proc/net/dev | awk '/$interface/ {print $10}'");
        $rx2 = intval($rx2);
        $tx2 = intval($tx2);

        // calculate speed in Mbps
        $rxMbps = (($rx2 - $rx1) * 8) / 1_000_000;
        $txMbps = (($tx2 - $tx1) * 8) / 1_000_000;

        // ping google dns
        $pingOutput = shell_exec("ping -c 1 $pingToIp | grep 'time=' | awk -F'time=' '{print $2}' | awk '{print $1}'");
        if ($pingOutput != false) {
            $ping = trim($pingOutput) ?: "N/A";
        } else {
            $ping = "N/A";
        }

        // calculate usage in %
        $usagePercent = (($rxMbps + $txMbps) / ($maxSpeedMbps * 2)) * 100;
        $networkUsagePercent = round($usagePercent, 2);
        if ($networkUsagePercent == 0.0) {
            $networkUsagePercent = 0.1;
        }

        return [
            'pingToIp' => $pingToIp,
            'interface' => $interface,
            'lastCheckTime' => date('H:i:s'),
            'uploadMbps' => round($txMbps, 2),
            'downloadMbps' => round($rxMbps, 2),
            'networkUsagePercent' => $networkUsagePercent,
            'pingMs' => is_numeric($ping) ? round(floatval($ping), 2) : "N/A"
        ];
    }

    /**
     * Get public IP address of the host server
     *
     * @return string|null The public IP address
     */
    public function getPublicIP(): ?string
    {
        // check if host IP is cached
        if ($this->cacheUtil->isCatched('host-ip')) {
            return (string) $this->cacheUtil->getValue('host-ip')->get();
        }

        // get list of IP APIs
        $apis = explode(',', $this->appUtil->getEnvValue('IP_APIS'));

        foreach ($apis as $api) {
            $ch = curl_init($api);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_FAILONERROR => true,
            ]);

            $ip = (string) curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($ip && $httpCode === Response::HTTP_OK && filter_var($ip, FILTER_VALIDATE_IP)) {
                $hostIp = trim($ip);
                $this->cacheUtil->setValue('host-ip', $hostIp, 86400);
                return $hostIp;
            }
        }

        return null;
    }

    /**
     * Get list of required applications that are not installed
     *
     * This method reads JSON file containing list of required applications
     * and checks if each application is installed
     *
     * @return array<string> List of applications that are not installed
     */
    public function getNotInstalledRequirements(): array
    {
        $notFoundApps = [];

        /** @var array<array<string>> $appList get list of required apps */
        $appList = $this->appUtil->loadConfig('package-requirements.json');

        /** @var array<string> $systemPackages list of system packages */
        $systemPackages = $appList['system-packages'] ?? null;

        /** @var array<string> $phpExtensions list of php extensions */
        $phpExtensions = $appList['php-extensions'] ?? null;

        // check if system packages list is null
        if (is_null($systemPackages)) {
            $this->errorManager->handleError(
                message: 'error to get not installed requirements: system-packages list is null',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if php extensions list is null
        if (is_null($phpExtensions)) {
            $this->errorManager->handleError(
                message: 'error to get not installed requirements: php-extensions list is null',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if app is installed
        if ($systemPackages != null) {
            foreach ($systemPackages as $app) {
                if (!$this->isServiceInstalled($app)) {
                    array_push($notFoundApps, $app);
                }
            }
        }

        // check if php extension is installed
        if ($phpExtensions != null) {
            foreach ($phpExtensions as $extension) {
                if (!$this->isPhpExtensionInstalled($extension)) {
                    array_push($notFoundApps, 'php-' . $extension);
                }
            }
        }

        // return not found requirements
        return $notFoundApps;
    }

    /**
     * Get list of running processes
     *
     * @return array<array<string>> List of running processes
     */
    public function getProcessList(): ?array
    {
        $processes = [];

        try {
            // open process for reading
            $process = proc_open('ps aux', [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ], $pipes);

            if (is_resource($process)) {
                // read stdout
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                // read stderr
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                // close process
                $returnValue = proc_close($process);

                // handle process list get error
                if ($returnValue !== 0) {
                    $this->errorManager->handleError(
                        message: 'error getting process list: ' . $errors,
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // check if output is null
                if ($output == null) {
                    $this->errorManager->handleError(
                        message: 'error getting process list: output is null',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // split output into lines
                $lines = explode("\n", $output);

                // remove header line
                array_shift($lines);

                foreach ($lines as $line) {
                    /** @var list<string>|false $parts */
                    $parts = preg_split('/\s+/', $line);

                    // check if parts is countable
                    if (!is_countable($parts)) {
                        continue;
                    }

                    if (count($parts) > 10) {
                        $pid = $parts[1];
                        $user = $parts[0];
                        $processName = implode(' ', array_slice($parts, 10));
                        $processes[] = [
                            'pid' => $pid,
                            'user' => $user,
                            'process' => $processName,
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting process list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $processes;
    }

    /**
     * Check if system reboot is required
     *
     * @return bool True if reboot is required, false otherwise
     */
    public function isRebootRequired(): bool
    {
        // debian check
        if (file_exists('/run/reboot-required')) {
            return true;
        }

        // rhel/centos check
        if (file_exists('/var/run/reboot-required') || file_exists('/var/run/reboot-required.pkgs')) {
            return true;
        }

        // check with "needs-restarting" (if available)
        $needsRestart = shell_exec('command -v needs-restarting && needs-restarting -r 2>/dev/null');
        if (!empty($needsRestart)) {
            return true;
        }

        return false;
    }

    /**
     * Check if system update is available
     *
     * @return bool True if update is available, false otherwise
     */
    public function isUpdateAvailable(): bool
    {
        // debian check
        if (file_exists('/usr/bin/apt-get')) {
            $updates = shell_exec('apt list --upgradable 2>/dev/null | grep -c "upgradable"');
            if (!$updates) {
                return false;
            }
            if (intval(trim($updates)) > 0) {
                return true;
            }
        }

        // rhel/centos check
        if (file_exists('/usr/bin/dnf')) {
            $updates = shell_exec('dnf check-update 2>/dev/null | grep -c "Available Packages"');
            if (!$updates) {
                return false;
            }
            if (intval(trim($updates)) > 0) {
                return true;
            }
        }

        // fedora check
        if (file_exists('/usr/bin/yum')) {
            $updates = shell_exec('yum check-update 2>/dev/null | grep -Evc "^\s*$"');
            if (!$updates) {
                return false;
            }
            if (intval(trim($updates)) > 0) {
                return true;
            }
        }

        // arch check
        if (file_exists('/usr/bin/pacman')) {
            $updates = shell_exec('checkupdates 2>/dev/null | wc -l');
            if (!$updates) {
                return false;
            }
            if (intval(trim($updates)) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if directory has 777 permissions
     *
     * @param string $folderPath The folder path
     *
     * @return bool True if directory has 777 permissions, false otherwise
     */
    public function checkIfDirectoryHas777Permissions(string $folderPath): bool
    {
        // check if path is directory
        if (!is_dir($folderPath)) {
            return false;
        }

        // check if folder has 777 permissions
        $permissions = substr(sprintf('%o', fileperms($folderPath)), -3);
        return $permissions === '777';
    }

    /**
     * Get last monitoring time
     *
     * @return string|null The last monitoring time
     */
    public function getLastMonitoringTime(): ?string
    {
        // check if last monitoring time is cached
        if (!$this->cacheUtil->isCatched('last-monitoring-time')) {
            return null;
        }

        // get last monitoring time
        $lastMonitoringTime = $this->cacheUtil->getValue('last-monitoring-time');

        // format last monitoring time to string
        $lastMonitoringTime = $lastMonitoringTime->get();
        return $lastMonitoringTime->format('Y-m-d H:i:s');
    }

    /**
     * Get diagnostic data
     *
     * @return array<string,mixed> The diagnostic data
     */
    public function getDiagnosticData(): array
    {
        // get diagnostic data
        $isSSL = $this->appUtil->isSsl();
        $cpuUsage = $this->getCpuUsage();
        $webUsername = $this->getWebUsername();
        $isWebUserSudo = $this->isWebUserSudo();
        $isDevMode = $this->appUtil->isDevMode();
        $ramUsage = $this->getRamUsagePercentage();
        $rebootRequired = $this->isRebootRequired();
        $updateAvailable = $this->isUpdateAvailable();
        $driveSpace = $this->getDriveUsagePercentage();
        $lastMonitoringTime = $this->getLastMonitoringTime();
        $exceptionFilesList = $this->logManager->getExceptionFiles();
        $notInstalledRequirements = $this->getNotInstalledRequirements();
        $websiteCachePermissions = $this->checkIfDirectoryHas777Permissions($this->appUtil->getAppRootDir() . '/var');

        // check if last monitoring cached is expired (only if monitoring service is running)
        $isLastMonitoringTimeCached = true;
        if ($this->serviceManager->isServiceRunning('monitoring')) {
            $isLastMonitoringTimeCached = $this->cacheUtil->isCatched('last-monitoring-time');
        }

        return [
            'isSSL' => $isSSL,
            'cpuUsage' => $cpuUsage,
            'ramUsage' => $ramUsage,
            'isDevMode' => $isDevMode,
            'driveSpace' => $driveSpace,
            'webUsername' => $webUsername,
            'isWebUserSudo' => $isWebUserSudo,
            'rebootRequired' => $rebootRequired,
            'updateAvailable' => $updateAvailable,
            'lastMonitoringTime' => $lastMonitoringTime,
            'exceptionFilesList' => $exceptionFilesList,
            'notInstalledRequirements' => $notInstalledRequirements,
            'websiteDirectoryPermissions' => $websiteCachePermissions,
            'isLastMonitoringTimeCached' => $isLastMonitoringTimeCached
        ];
    }
}
