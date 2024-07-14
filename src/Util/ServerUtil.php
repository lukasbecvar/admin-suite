<?php

namespace App\Util;

use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ServerUtil
 *
 * Utility class for server information
 *
 * @package App\Util
 */
class ServerUtil
{
    private JsonUtil $jsonUtil;
    private ErrorManager $errorManager;

    public function __construct(JsonUtil $jsonUtil, ErrorManager $errorManager)
    {
        $this->jsonUtil = $jsonUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Get the host uptime.
     *
     * @return string The formatted host uptime.
     */
    public function getHostUptime(): string
    {
        // get host uptime
        $up_time = (int) strtok((string) exec('cat /proc/uptime'), '.');

        // get uptime values
        $days = sprintf('%2d', ($up_time / (3600 * 24)));
        $hours = sprintf('%2d', (($up_time % (3600 * 24)) / 3600));
        $min = sprintf('%2d', ($up_time % (3600 * 24) % 3600) / 60);

        // format output
        return 'Days: ' . $days . ', Hours: ' . $hours . ', Min: ' . $min;
    }

    /**
     * Get the CPU usage percentage.
     *
     * @return float The CPU usage percentage.
     */
    public function getCpuUsage(): float
    {
        $load = 100;
        $loads = sys_getloadavg();

        // check if sys_getloadavg() returned an array
        if (!is_array($loads) || count($loads) < 1) {
            return $load; // return default value if load average is unavailable
        }

        // fetch number of CPU cores
        $coreNums = (string) shell_exec("grep -P '^processor' /proc/cpuinfo | wc -l");

        // validate core nums
        if ($coreNums !== null && trim($coreNums) !== '') {
            $coreNums = trim($coreNums);
            $load = round($loads[0] / (intval($coreNums) + 1) * 100, 2);
        }

        // overload fix
        if ($load > 100) {
            $load = 100;
        }

        return $load;
    }

    /**
     * Get the RAM usage information.
     *
     * @return array<string, string> An array containing RAM usage information.
     */
    public function getRamUsage(): array
    {
        exec('cat /proc/meminfo', $memoryRaw);
        $memoryFree = 0;
        $memoryTotal = 0;
        $memoryUsed = 0;

        foreach ($memoryRaw as $line) {
            if (strstr($line, 'MemTotal')) {
                $memoryTotal = filter_var($line, FILTER_SANITIZE_NUMBER_INT);
                if ($memoryTotal !== false) {
                    $memoryTotal = (float) $memoryTotal / 1048576;
                }
            }
            if (strstr($line, 'MemFree')) {
                $memoryFree = filter_var($line, FILTER_SANITIZE_NUMBER_INT);
                if ($memoryFree !== false) {
                    $memoryFree = (float) $memoryFree / 1048576;
                }
            }
        }

        $memoryUsed = $memoryTotal - $memoryFree;

        return array(
            'used'  => number_format($memoryUsed, 2),
            'free'  => number_format((float) $memoryFree, 2),
            'total' => number_format((float) $memoryTotal, 2)
        );
    }

    /**
     * Get the RAM usage percentage.
     *
     * @return int The RAM usage percentage.
     */
    public function getRamUsagePercentage(): int
    {
        $ramUsageData = $this->getRamUsage();

        // calculate percentage
        $usage = (int) (((float) $ramUsageData['used'] / (float) $ramUsageData['total']) * 100);

        return $usage;
    }

    /**
     * Get the disk usage.
     *
     * @return int|null The disk usage.
     */
    public function getDiskUsage(): ?int
    {
        try {
            return (int) exec("df --output=used -BG / | awk 'NR==2 { print int($1) }'");
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get disk usage ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return null;
        }
    }

    /**
     * Get the drive usage percentage.
     *
     * @return string|null The drive usage percentage or null on error.
     */
    public function getDriveUsagePercentage(): ?string
    {
        try {
            return (string) exec("df -Ph / | awk 'NR == 2{print $5}' | tr -d '%'");
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get drive usage percentage ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return null;
        }
    }

    /**
     * Get the web username.
     *
     * @return string|null The web username or null on error.
     */
    public function getWebUsername(): ?string
    {
        try {
            return (string) exec('whoami');
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get web username ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return null;
        }
    }

    /**
     * Check if the system is running Linux.
     *
     * @return bool True if the system is running Linux, false otherwise.
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
     * Check if the web user has sudo privileges.
     *
     * @return bool True if the web user has sudo privileges, false otherwise.
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
     * Get information about installed software packages and the Linux distribution.
     *
     * @return array<mixed> An array containing information about installed software packages and the Linux distribution. *
     */
    public function getSystemInfo(): array
    {
        $distro = array();
        exec('rpm -qai | grep "Name        :\|Version     :\|Release     :\|Install Date:\|Group       :\|Size        :"', $softwareRaw);
        exec('uname -mrs', $distroRaw);
        exec('cat /etc/*-release', $distroNameRaw);
        $distroParts = explode(' ', $distroRaw[0]);
        $distro['operating_system'] = $distroNameRaw[0];
        $distro['kernel_version'] = $distroParts[0] . ' ' . $distroParts[1];
        $distro['kernel_arch'] = $distroParts[2];
        return $distro;
    }

    /**
     * Checks if a service is or is php extension installed.
     *
     * @param string $serviceName The name of the service.
     *
     * @return bool The service is installed, false otherwise.
     */
    public function isServiceInstalled(string $serviceName): bool
    {
        // check dpkg package
        exec('dpkg -l | grep ' . escapeshellarg($serviceName), $output, $returnCode);

        if ($returnCode === 0) {
            return true;
        }

        // check php extension
        if (extension_loaded($serviceName)) {
            return true;
        }

        return false;
    }

    /**
     * Get a list of required applications that are not installed.
     *
     * This method reads a JSON file containing a list of required applications
     * and checks if each application is installed. It returns an array of applications
     * that are not found on the system.
     *
     * @return array<string> List of applications that are not installed.
     */
    public function getNotInstalledRequirements(): array
    {
        $notFoundApps = [];

        // default example config path
        $configPath = __DIR__ . '/../../config/suite/package-requirements.json';

        // set config path
        if (file_exists(__DIR__ . '/../../package-requirements.json')) {
            $configPath = __DIR__ . '/../../package-requirements.json';
        }

        /** @var array<string> $appList get list of required apps */
        $appList = $this->jsonUtil->getJson($configPath);

        if (is_iterable($appList)) {
            // check if app is installed
            foreach ($appList as $app) {
                if (!$this->isServiceInstalled($app)) {
                    array_push($notFoundApps, $app);
                }
            }
        }

        // return not found requirements
        return $notFoundApps;
    }

    /**
     * Get a list of running processes.
     *
     * @return array<array<string>> List of running processes.
     */
    public function getProcessList(): ?array
    {
        $output = [];

        try {
            exec('ps aux', $output);
        } catch (\Exception $exception) {
            $this->errorManager->handleError(
                message: 'error to get process list ' . $exception->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return null;
        }

        // remove the first line (header) from the output
        if (count($output) > 0) {
            unset($output[0]);
        }

        $processes = [];

        foreach ($output as $line) {
            // split the line into parts based on whitespace
            $parts = preg_split('/\s+/', $line);

            // check if the line contains parts
            if ($parts) {
                // extract PID, User, and Process
                $pid = $parts[1];
                $user = $parts[0];

                // combine the remaining parts into the process name
                $process = implode(' ', array_slice($parts, 10));

                // create an array with PID, User, and Process
                $processes[] = [
                    'pid' => $pid,
                    'user' => $user,
                    'process' => $process,
                ];
            }
        }

        return $processes;
    }

    /**
     * Executes a command
     *
     * @param string $command The command to execute
     *
     * @return void
     */
    public function executeCommand($command): void
    {
        try {
            exec($command);
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to executed command: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
