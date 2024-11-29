<?php

namespace App\Util;

use Exception;
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
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ErrorManager $errorManager,
        ServiceManager $serviceManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Get the host uptime
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
     * Get the CPU usage percentage
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

        // validate the number of cores obtained
        if ($coreNums > 0) {
            // calculate CPU usage in percentage
            $load = round(min($loads[0] / $coreNums * 100, 100), 2);
        }

        return max($load, 0.1);
    }

    /**
     * Get the RAM usage information
     *
     * @return array<string,string> An array containing RAM usage information
     */
    public function getRamUsage(): array
    {
        $memoryRaw = file_get_contents('/proc/meminfo');
        $memoryFree = 0;
        $memoryTotal = 0;

        if ($memoryRaw !== false) {
            $lines = explode("\n", $memoryRaw);
            foreach ($lines as $line) {
                if (str_contains($line, 'MemTotal:')) {
                    $memoryTotal = (float) filter_var($line, FILTER_SANITIZE_NUMBER_INT) / 1048576;
                } elseif (str_contains($line, 'MemFree:')) {
                    $memoryFree = (float) filter_var($line, FILTER_SANITIZE_NUMBER_INT) / 1048576;
                }
            }
        }

        // calculate memory usage
        $memoryUsed = $memoryTotal - $memoryFree;

        return [
            'used'  => number_format($memoryUsed, 2),
            'free'  => number_format($memoryFree, 2),
            'total' => number_format($memoryTotal, 2)
        ];
    }

    /**
     * Get the RAM usage percentage
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
     * Get the storage usage
     *
     * @throws Exception If an error occurs while getting the storage usage
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
                message: 'Error getting storage usage: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get the drive usage percentage
     *
     * @throws Exception If an error occurs while getting the drive usage percentage
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
                return null; // handle case where total space is 0 to avoid division by zero
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting drive usage percentage: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get the web username
     *
     * @throws Exception If an error occurs while getting the web username
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
     * Check if the host system is linux
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
     * Check if the web user has sudo privileges
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
     * Get information about installed software packages and the Linux distribution
     *
     * @return array<mixed> An array containing information about installed software packages and the Linux distribution
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
     * Check if a service is or is php extension installed
     *
     * @param string $serviceName The name of the service
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

        // get the list of installed dpkg packages
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
     * Check if a PHP extension is installed
     *
     * @param string $extension The name of the PHP extension
     *
     * @return bool True if the PHP extension is installed, false otherwise
     */
    public function isPhpExtensionInstalled(string $extension): bool
    {
        return extension_loaded($extension);
    }

    /**
     * Get a list of required applications that are not installed
     *
     * This method reads a JSON file containing a list of required applications
     * and checks if each application is installed
     *
     * @throws Exception If an error occurs while loading the package-requirements.json file
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
     * Get a list of running processes
     *
     * @throws Exception If an error occurs while getting the process list
     *
     * @return array<array<string>> List of running processes
     */
    public function getProcessList(): ?array
    {
        $processes = [];

        try {
            // open process for reading
            $process = proc_open('ps aux', [
                1 => ['pipe', 'w'],  // stdout is a pipe that we read from
                2 => ['pipe', 'w']   // stderr is a pipe that we read from
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

                // remove the header line
                array_shift($lines);

                foreach ($lines as $line) {
                    /** @var list<string>|false $parts */
                    $parts = preg_split('/\s+/', $line);

                    // check f parts is countable
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
        $driveSpace = $this->getDriveUsagePercentage();
        $notInstalledRequirements = $this->getNotInstalledRequirements();

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
            'notInstalledRequirements' => $notInstalledRequirements,
            'isLastMonitoringTimeCached' => $isLastMonitoringTimeCached
        ];
    }
}
