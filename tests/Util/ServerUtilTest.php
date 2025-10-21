<?php

namespace Tests\Unit\Util;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ServerUtilTest
 *
 * Test cases for server util
 *
 * @package Tests\Unit\Util
 */
#[CoversClass(ServerUtil::class)]
class ServerUtilTest extends TestCase
{
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private LogManager & MockObject $logManagerMock;
    private ServiceManager & MockObject $serviceManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);

        // create server util instance
        $this->serverUtil = new ServerUtil(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->logManagerMock,
            $this->errorManager,
            $this->serviceManagerMock
        );
    }

    /**
     * Test get host uptime
     *
     * @return void
     */
    public function testGetHostUptime(): void
    {
        // call tested method
        $result = $this->serverUtil->getHostUptime();

        // assert result
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test get cpu usage
     *
     * @return void
     */
    public function testGetCpuUsage(): void
    {
        // call tested method
        $result = $this->serverUtil->getCpuUsage();

        // assert result
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertLessThanOrEqual(100, $result);
    }

    /**
     * Test get ram usage
     *
     * @return void
     */
    public function testGetRamUsage(): void
    {
        // call tested method
        $result = $this->serverUtil->getRamUsage();

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('used', $result);
        $this->assertArrayHasKey('free', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * Test get ram usage percentage
     *
     * @return void
     */
    public function testGetRamUsagePercentage(): void
    {
        // call tested method
        $result = $this->serverUtil->getRamUsagePercentage();

        // assert result
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertLessThanOrEqual(100, $result);
    }

    /**
     * Test get storage usage
     *
     * @return void
     */
    public function testGetStorageUsage(): void
    {
        // call tested method
        $result = $this->serverUtil->getStorageUsage();

        // assert result
        $this->assertIsInt($result);
    }

    /**
     * Test get drive usage percentage
     *
     * @return void
     */
    public function testGetDriveUsagePercentage(): void
    {
        // call tested method
        $result = $this->serverUtil->getDriveUsagePercentage();

        // assert result
        $this->assertIsString($result);
        $this->assertIsNumeric($result);
    }

    /**
     * Test get web username
     *
     * @return void
     */
    public function testGetWebUsername(): void
    {
        // call tested method
        $result = $this->serverUtil->getWebUsername();

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test check is system linux
     *
     * @return void
     */
    public function testCheckIsSystemLinux(): void
    {
        // call tested method
        $result = $this->serverUtil->isSystemLinux();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check is system linux
     *
     * @return void
     */
    public function testIsSystemLinux(): void
    {
        // call tested method
        $isLinux = $this->serverUtil->isSystemLinux();

        // assert that the result is a boolean value
        $this->assertIsBool($isLinux);
    }

    /**
     * Test check is web user sudo
     *
     * @return void
     */
    public function testIsWebUserSudo(): void
    {
        // call tested method
        $result = $this->serverUtil->isWebUserSudo();

        // assert result
        $this->assertIsBool($result);
    }


    /**
     * Test get software info
     *
     * @return void
     */
    public function testGetSoftwareInfo(): void
    {
        // call tested method
        $result = $this->serverUtil->getSystemInfo();

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('kernel_version', $result);
        $this->assertArrayHasKey('kernel_arch', $result);
        $this->assertArrayHasKey('operating_system', $result);
    }

    /**
     * Test get system installation date
     *
     * @return void
     */
    public function testGetSystemInstallInfo(): void
    {
        // call tested method
        $result = $this->serverUtil->getSystemInstallInfo();

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test check if a service is installed
     *
     * @return void
     */
    public function testCheckIsServiceInstalled(): void
    {
        // call tested method
        $result = $this->serverUtil->isServiceInstalled('nginx');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check if a php extension is installed
     *
     * @return void
     */
    public function testCheckIsPhpExtensionInstalled(): void
    {
        // call tested method
        $result = $this->serverUtil->isPhpExtensionInstalled('curl');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get network stats
     *
     * @return void
     */
    public function testGetNetworkStats(): void
    {
        // mock env value
        $this->appUtilMock->expects($this->once())->method('getEnvValue')
            ->with('NETWORK_SPEED_MAX')->willReturn('1000');

        // call tested method
        $result = $this->serverUtil->getNetworkStats();

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pingToIp', $result);
        $this->assertArrayHasKey('interface', $result);
        $this->assertArrayHasKey('lastCheckTime', $result);
        $this->assertArrayHasKey('uploadMbps', $result);
        $this->assertArrayHasKey('downloadMbps', $result);
        $this->assertArrayHasKey('networkUsagePercent', $result);
        $this->assertArrayHasKey('pingMs', $result);
    }

    /**
     * Test get not installed requirements
     *
     * @return void
     */
    public function testGetNotInstalledRequirements(): void
    {
        // call tested method
        $result = $this->serverUtil->getNotInstalledRequirements();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get process list
     *
     * @return void
     */
    public function testGetProcessList(): void
    {
        // call tested method
        $result = $this->serverUtil->getProcessList();

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pid', $result[0]);
        $this->assertArrayHasKey('user', $result[0]);
        $this->assertArrayHasKey('process', $result[0]);
    }

    /**
     * Test check if system reboot is required
     *
     * @return void
     */
    public function testIsRebootRequired(): void
    {
        // call tested method
        $result = $this->serverUtil->isRebootRequired();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check if system update is available
     *
     * @return void
     */
    public function testIsUpdateAvailable(): void
    {
        // call tested method
        $result = $this->serverUtil->isUpdateAvailable();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check if directory has 777 permissions
     *
     * @return void
     */
    public function testCheckIfDirectoryHas777Permissions(): void
    {
        // call tested method
        $result = $this->serverUtil->checkIfDirectoryHas777Permissions('/var');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get ufw open ports
     *
     * @return void
     */
    public function testGetUfwOpenPorts(): void
    {
        // call tested method
        $result = $this->serverUtil->getUfwOpenPorts();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get linux users
     *
     * @return void
     */
    public function testGetLinuxUsers(): void
    {
        // call tested method
        $users = $this->serverUtil->getLinuxUsers();

        // assert result
        $this->assertIsArray($users);
        $this->assertNotEmpty($users[0]);
        $this->assertArrayHasKey('username', $users[0]);
        $this->assertArrayHasKey('uid', $users[0]);
        $this->assertArrayHasKey('gid', $users[0]);
        $this->assertArrayHasKey('home', $users[0]);
        $this->assertArrayHasKey('shell', $users[0]);
        $this->assertArrayHasKey('has_sudo', $users[0]);
        $this->assertIsString($users[0]['username']);
        $this->assertIsInt($users[0]['uid']);
        $this->assertIsInt($users[0]['gid']);
        $this->assertIsString($users[0]['home']);
        $this->assertIsString($users[0]['shell']);
        $this->assertIsBool($users[0]['has_sudo']);
    }

    /**
     * Test check if directory is too big
     *
     * @return void
     */
    public function testCheckIfDirectoryIsTooBig(): void
    {
        // call tested method
        $result = $this->serverUtil->checkIfDirectoryIsTooBig('/var/log', 20);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get diagnostic data
     *
     * @return void
     */
    public function testGetDiagnosticData(): void
    {
        // call tested method
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // assert that the result is an array
        $this->assertIsArray($diagnosticData);

        // assert that the result contains the expected keys
        $this->assertArrayHasKey('isSSL', $diagnosticData);
        $this->assertArrayHasKey('cpuUsage', $diagnosticData);
        $this->assertArrayHasKey('ramUsage', $diagnosticData);
        $this->assertArrayHasKey('isDevMode', $diagnosticData);
        $this->assertArrayHasKey('driveSpace', $diagnosticData);
        $this->assertArrayHasKey('webUsername', $diagnosticData);
        $this->assertArrayHasKey('isWebUserSudo', $diagnosticData);
        $this->assertArrayHasKey('rebootRequired', $diagnosticData);
        $this->assertArrayHasKey('updateAvailable', $diagnosticData);
        $this->assertArrayHasKey('notInstalledRequirements', $diagnosticData);
        $this->assertArrayHasKey('isLastMonitoringTimeCached', $diagnosticData);
        $this->assertArrayHasKey('websiteDirectoryPermissions', $diagnosticData);
    }
}
