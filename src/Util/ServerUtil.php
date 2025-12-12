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
        $start = $this->readPerCoreCpuStats();

        // brief pause to capture delta without blocking too long
        usleep(100000);
        $end = $this->readPerCoreCpuStats();

        if (empty($start) || empty($end)) {
            return 0.0;
        }

        $usages = [];
        foreach ($start as $core => $startStats) {
            if (!isset($end[$core])) {
                continue;
            }

            $totalDiff = $end[$core]['total'] - $startStats['total'];
            $idleDiff = $end[$core]['idle'] - $startStats['idle'];

            if ($totalDiff <= 0) {
                continue;
            }

            $usage = (1 - ($idleDiff / $totalDiff)) * 100;
            $usages[] = max(0, min($usage, 100));
        }

        if (empty($usages)) {
            return 0.1;
        }

        $average = array_sum($usages) / count($usages);
        $usage = round($average, 2);

        // prevent negative usage
        if ($usage < 0.1) {
            $usage = 0.1;
        }

        return $usage;
    }

    /**
     * Read per-core CPU stats from /proc/stat
     *
     * @return array<string,array<string,int>> keyed by cpu index (e.g. cpu0)
     */
    private function readPerCoreCpuStats(): array
    {
        $stats = [];
        $lines = @file('/proc/stat', FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return $stats;
        }

        foreach ($lines as $line) {
            if (!str_starts_with($line, 'cpu')) {
                continue;
            }

            // skip the aggregated "cpu " line to keep per-core data only
            if (preg_match('/^cpu\\s+/', $line)) {
                continue;
            }

            $parts = preg_split('/\\s+/', trim($line));
            if (!$parts || count($parts) < 5) {
                continue;
            }

            $coreId = $parts[0];
            $values = array_map('intval', array_slice($parts, 1));
            $idle = $values[3] + ($values[4] ?? 0); // idle + iowait
            $total = array_sum($values);

            $stats[$coreId] = [
                'idle' => $idle,
                'total' => $total
            ];
        }

        return $stats;
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

            // return formatted date
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
     * @param string|null $interface The network interface (default: get from env NETWORK_INTERFACE)
     * @param string $pingToIp The IP address to ping (default: 8.8.8.8)
     *
     * @return array<string,float|string> The network statistics
     */
    public function getNetworkStats(?string $interface = null, string $pingToIp = '8.8.8.8'): array
    {
        $interface = $interface ?: $this->appUtil->getEnvValue('NETWORK_INTERFACE') ?: 'eth0';

        // get network speed max from env (in Mbps) or try to auto-detect
        $maxSpeedMbps = (int) $this->appUtil->getEnvValue('NETWORK_SPEED_MAX');
        if ($maxSpeedMbps <= 0) {
            $detectedSpeed = $this->detectInterfaceSpeed($interface);
            if ($detectedSpeed !== null) {
                $maxSpeedMbps = $detectedSpeed;
            }
        }
        if ($maxSpeedMbps <= 0) {
            $maxSpeedMbps = 100; // sensible default when detection fails
        }

        $firstSample = $this->readInterfaceCounters($interface);
        if ($firstSample === null) {
            return $this->buildEmptyNetworkStats($interface, $pingToIp);
        }

        // wait 1 second before second measurement
        usleep(1_000_000);

        $secondSample = $this->readInterfaceCounters($interface);
        if ($secondSample === null) {
            return $this->buildEmptyNetworkStats($interface, $pingToIp);
        }

        $rxDelta = max(0, $secondSample['rx'] - $firstSample['rx']);
        $txDelta = max(0, $secondSample['tx'] - $firstSample['tx']);

        // calculate speed in Mbps
        $rxMbps = ($rxDelta * 8) / 1_000_000;
        $txMbps = ($txDelta * 8) / 1_000_000;

        $downloadUsagePercent = $this->calculateUsagePercent($rxMbps, $maxSpeedMbps);
        $uploadUsagePercent = $this->calculateUsagePercent($txMbps, $maxSpeedMbps);
        $networkUsagePercent = $this->calculateBidirectionalUsagePercent($rxMbps, $txMbps, $maxSpeedMbps);

        // ping destination (default Google DNS)
        $pingCommand = sprintf(
            "ping -c 1 %s 2>/dev/null | grep 'time=' | awk -F'time=' '{print $2}' | awk '{print $1}'",
            escapeshellarg($pingToIp)
        );
        $pingOutput = shell_exec($pingCommand);
        if (is_string($pingOutput)) {
            $ping = trim($pingOutput) ?: "N/A";
        } else {
            $ping = "N/A";
        }

        return [
            'pingToIp' => $pingToIp,
            'interface' => $interface,
            'lastCheckTime' => date('H:i:s'),
            'uploadMbps' => round($txMbps, 2),
            'downloadMbps' => round($rxMbps, 2),
            'uploadUsagePercent' => $uploadUsagePercent,
            'networkUsagePercent' => $networkUsagePercent,
            'downloadUsagePercent' => $downloadUsagePercent,
            'linkSpeedMbps' => (float) $maxSpeedMbps,
            'pingMs' => is_numeric($ping) ? round((float) $ping, 2) : "N/A"
        ];
    }

    /**
     * Build a default network stats payload when counters are unavailable
     *
     * @param string $interface The network interface
     * @param string $pingToIp The IP address to ping
     *
     * @return array<string,float|string> The default network stats payload
     */
    public function buildEmptyNetworkStats(string $interface, string $pingToIp): array
    {
        return [
            'pingToIp' => $pingToIp,
            'interface' => $interface,
            'lastCheckTime' => date('H:i:s'),
            'uploadMbps' => 0.0,
            'downloadMbps' => 0.0,
            'uploadUsagePercent' => 0.0,
            'downloadUsagePercent' => 0.0,
            'networkUsagePercent' => 0.0,
            'linkSpeedMbps' => 'N/A',
            'pingMs' => "N/A"
        ];
    }

    /**
     * Read RX/TX counters from /proc/net/dev for a given interface
     *
     * @param string $interface The network interface
     *
     * @return array{rx:int,tx:int}|null The RX/TX counters or null on error
     */
    public function readInterfaceCounters(string $interface): ?array
    {
        $interface = trim($interface);
        if ($interface === '') {
            return null;
        }

        $lines = @file('/proc/net/dev', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $stats] = array_map('trim', explode(':', $line, 2));
            if ($name !== $interface) {
                continue;
            }

            $columns = preg_split('/\s+/', trim($stats));
            if (!is_array($columns) || count($columns) < 10) {
                return null;
            }

            return [
                'rx' => (int) $columns[0],
                'tx' => (int) $columns[8],
            ];
        }

        return null;
    }

    /**
     * Attempt to detect the link speed for a network interface
     *
     * @param string $interface The network interface
     *
     * @return int|null The link speed in Mbps or null on error
     */
    public function detectInterfaceSpeed(string $interface): ?int
    {
        $interface = trim($interface);
        if ($interface === '') {
            return null;
        }

        $speedPath = '/sys/class/net/' . basename($interface) . '/speed';
        if (is_readable($speedPath)) {
            $speedRaw = trim((string) file_get_contents($speedPath));
            if (is_numeric($speedRaw)) {
                $speedValue = (int) $speedRaw;
                if ($speedValue > 0) {
                    return $speedValue;
                }
            }
        }

        return null;
    }

    /**
     * Calculate usage percent for a given Mbps value and link speed
     *
     * @param float $mbps The Mbps value
     * @param int $linkSpeed The link speed in Mbps
     *
     * @return float The usage percent
     */
    public function calculateUsagePercent(float $mbps, int $linkSpeed): float
    {
        if ($linkSpeed <= 0) {
            return 0.0;
        }

        $usage = ($mbps / $linkSpeed) * 100;

        return round(min(max($usage, 0.0), 100.0), 2);
    }

    /**
     * Calculate combined upload+download utilization assuming full-duplex link
     *
     * @param float $downloadMbps The download Mbps
     * @param float $uploadMbps The upload Mbps
     * @param int $linkSpeed The link speed in Mbps
     *
     * @return float The combined utilization percent
     */
    public function calculateBidirectionalUsagePercent(float $downloadMbps, float $uploadMbps, int $linkSpeed): float
    {
        if ($linkSpeed <= 0) {
            return 0.0;
        }

        $combinedCapacity = $linkSpeed * 2;
        $combinedUsage = (($downloadMbps + $uploadMbps) / $combinedCapacity) * 100;

        return round(min(max($combinedUsage, 0.0), 100.0), 2);
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
                CURLOPT_FAILONERROR => true
            ]);

            $ip = (string) curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

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
            $procDir = '/proc';
            $pids = array_filter(scandir($procDir), fn($pid) => is_numeric($pid));

            foreach ($pids as $pid) {
                $statusFile = "$procDir/$pid/status";
                $cmdlineFile = "$procDir/$pid/cmdline";

                if (!is_readable($statusFile) || !is_readable($cmdlineFile)) {
                    continue;
                }

                // read status file to get user info
                $statusContent = file_get_contents($statusFile);
                if (!$statusContent) {
                    continue;
                }
                preg_match('/Uid:\s+(\d+)/', $statusContent, $matches);
                $uid = $matches[1] ?? 'unknown';

                // resolve UID to username
                $user = posix_getpwuid((int) $uid)['name'] ?? 'unknown';

                // read command line file to get process name
                $cmdline = file_get_contents($cmdlineFile);
                if (!$cmdline) {
                    continue;
                }
                $processName = str_replace("\0", ' ', trim($cmdline)) ?: '[unknown]';

                $processes[] = [
                    'pid' => $pid,
                    'user' => $user,
                    'process' => $processName
                ];
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
     * Get ufw open ports
     *
     * @return array<array<string>|null> List of open ports
     */
    public function getUfwOpenPorts(): array
    {
        try {
            $output = shell_exec('sudo ufw status');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get ufw open ports: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($output == null || $output == false) {
            return [];
        }

        $lines = explode("\n", $output);
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // skip headers and separator line
            if (str_starts_with($line, 'To') || str_starts_with($line, '--')) {
                continue;
            }

            // split variable spacing
            $parts = preg_split('/\s{2,}/', $line);
            if (!is_countable($parts)) {
                continue;
            }

            if (count($parts) >= 3) {
                $result[] = [
                    'port_service' => $parts[0],
                    'action' => $parts[1],
                    'from' => $parts[2]
                ];
            }
        }

        return $result;
    }

    /**
     * Get list of Linux system users from /etc/passwd
     *
     * @return array<array<mixed>> List of user info
     */
    public function getLinuxUsers(): array
    {
        $users = [];
        $passwdLines = @file('/etc/passwd', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $groupLines = @file('/etc/group', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($passwdLines === false || $groupLines === false) {
            return [];
        }

        // parse sudo group members
        $sudoUsers = [];
        foreach ($groupLines as $line) {
            $parts = explode(':', $line);
            if (count($parts) === 4 && in_array($parts[0], ['sudo', 'wheel'])) {
                $groupUsers = explode(',', $parts[3]);
                $sudoUsers = array_merge($sudoUsers, $groupUsers);
            }
        }

        foreach ($passwdLines as $line) {
            $parts = explode(':', $line);
            if (count($parts) >= 7) {
                $username = $parts[0];

                // get user lock status via `passwd -S`
                $isLocked = false;
                $output = shell_exec('sudo passwd -S ' . escapeshellarg($username));
                if (is_string($output)) {
                    $statusParts = preg_split('/\s+/', trim($output));
                    if ($statusParts !== false) {
                        if (count($statusParts) >= 2 && $statusParts[0] === $username) {
                            $statusFlag = $statusParts[1];
                            $isLocked = in_array($statusFlag, ['L', 'LK']);
                        }
                    }
                }

                $users[] = [
                    'username'   => $username,
                    'uid'        => (int)$parts[2],
                    'gid'        => (int)$parts[3],
                    'home'       => $parts[5],
                    'shell'      => $parts[6],
                    'has_sudo'   => in_array($username, $sudoUsers),
                    'is_locked'  => $isLocked
                ];
            }
        }

        return $users;
    }

    /**
     * Check if directory is too big
     *
     * @param string $directoryPath The directory path
     * @param int $limitGB The limit in GB
     *
     * @return bool True if directory is too big, false otherwise
     */
    public function checkIfDirectoryIsTooBig(string $directoryPath, int $limitGB = 20): bool
    {
        // run command to get size in kilobytes
        $output = [];
        $returnVar = 0;
        exec("sudo du -sk {$directoryPath} 2>/dev/null", $output, $returnVar);

        // check if command was successful
        if ($returnVar !== 0 || empty($output)) {
            $this->errorManager->logError(
                message: 'error to get directory size: ' . $output[0],
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }

        // extract size in KB
        $sizeKB = (int) explode("\t", $output[0])[0];

        // convert limit to KB
        $limitKB = $limitGB * 1024 * 1024;

        return $sizeKB > $limitKB;
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
        $isLogsTooBig = $this->checkIfDirectoryIsTooBig('/var/log', 20);
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
            'isLogsTooBig' => $isLogsTooBig,
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
