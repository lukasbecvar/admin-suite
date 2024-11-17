<?php

namespace Tests\Unit\Util;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ServerUtilTest
 *
 * Test cases for server util
 *
 * @package Tests\Unit\Util
 */
class ServerUtilTest extends TestCase
{
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private ServiceManager & MockObject $serviceManagerMock;

    protected function setUp(): void
    {
        // create mock error manager
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);

        // create instance of ServerUtil
        $this->serverUtil = new ServerUtil(
            $this->appUtilMock,
            $this->cacheUtilMock,
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
     * Test get cpu load average
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
        $ramUsage = $this->serverUtil->getRamUsage();

        // assert that the result is an array with keys 'used', 'free', and 'total'
        $this->assertIsArray($ramUsage);
        $this->assertArrayHasKey('used', $ramUsage);
        $this->assertArrayHasKey('free', $ramUsage);
        $this->assertArrayHasKey('total', $ramUsage);
    }

    /**
     * Test get ram usage percentage
     *
     * @return void
     */
    public function testGetRamUsagePercentage(): void
    {
        // call tested method
        $ramUsagePercentage = $this->serverUtil->getRamUsagePercentage();

        // assert that the result is an integer between 0 and 100
        $this->assertIsInt($ramUsagePercentage);
        $this->assertGreaterThanOrEqual(0, $ramUsagePercentage);
        $this->assertLessThanOrEqual(100, $ramUsagePercentage);
    }

    /**
     * Test get software info
     *
     * @return void
     */
    public function testGetSoftwareInfo(): void
    {
        // call tested method
        $softwareInfo = $this->serverUtil->getSystemInfo();

        // assert that the result is an array with keys 'packages' and 'distro'
        $this->assertIsArray($softwareInfo);
    }

    /**
     * Test check is web user sudo
     *
     * @return void
     */
    public function testIsWebUserSudo(): void
    {
        // call tested method
        $isSudo = $this->serverUtil->isWebUserSudo();

        // assert that the result is a boolean value
        $this->assertIsBool($isSudo);
    }

    /**
     * Test get drive usage percentage
     *
     * @return void
     */
    public function testGetDriveUsagePercentage(): void
    {
        // call tested method
        $driveUsagePercentage = $this->serverUtil->getDriveUsagePercentage();

        // assert that the result is a string
        $this->assertIsString($driveUsagePercentage);
    }

    /**
     * Test get storage usage
     *
     * @return void
     */
    public function testGetStorageUsage(): void
    {
        // call tested method
        $storageUsage = $this->serverUtil->getStorageUsage();

        // assert that the result is an integer
        $this->assertIsInt($storageUsage);
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
     * Test get process list
     *
     * @return void
     */
    public function testGetProcessList(): void
    {
        // call tested method
        $processList = $this->serverUtil->getProcessList();

        // assert that the result is an array
        $this->assertIsArray($processList);
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
        $this->assertArrayHasKey('notInstalledRequirements', $diagnosticData);
    }
}
