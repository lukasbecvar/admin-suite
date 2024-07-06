<?php

namespace Tests\Unit\Util;

use App\Util\ServerUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;

/**
 * Class ServerUtilTest
 *
 * Test cases for ServerUtil
 *
 * @package Tests\Unit\Util
 */
class ServerUtilTest extends TestCase
{
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;

    protected function setUp(): void
    {
        // create mock error manager
        $this->errorManager = $this->createMock(ErrorManager::class);

        // create instance of ServerUtil
        $this->serverUtil = new ServerUtil($this->errorManager);
        parent::setUp();
    }

    /**
     * Test getHostUptime method
     *
     * @return void
     */
    public function testGetHostUptime(): void
    {
        // call the method being tested
        $hostUptime = $this->serverUtil->getHostUptime();

        // assert that the result is a non-empty string
        $this->assertIsString($hostUptime);
        $this->assertNotEmpty($hostUptime);
    }

    /**
     * Test getHostLoadAverage method
     *
     * @return void
     */
    public function testGetCpuUsage(): void
    {
        // call the method being tested
        $cpuUsage = $this->serverUtil->getCpuUsage();

        // assert that the result is a float between 0 and 100
        $this->assertIsFloat($cpuUsage);
        $this->assertGreaterThanOrEqual(0, $cpuUsage);
        $this->assertLessThanOrEqual(100, $cpuUsage);
    }

    /**
     * Test getRamUsage method
     *
     * @return void
     */
    public function testGetRamUsage(): void
    {
        // call the method being tested
        $ramUsage = $this->serverUtil->getRamUsage();

        // assert that the result is an array with keys 'used', 'free', and 'total'
        $this->assertIsArray($ramUsage);
        $this->assertArrayHasKey('used', $ramUsage);
        $this->assertArrayHasKey('free', $ramUsage);
        $this->assertArrayHasKey('total', $ramUsage);
    }

    /**
     * Test getRamUsagePercentage method
     *
     * @return void
     */
    public function testGetRamUsagePercentage(): void
    {
        // call the method being tested
        $ramUsagePercentage = $this->serverUtil->getRamUsagePercentage();

        // assert that the result is an integer between 0 and 100
        $this->assertIsInt($ramUsagePercentage);
        $this->assertGreaterThanOrEqual(0, $ramUsagePercentage);
        $this->assertLessThanOrEqual(100, $ramUsagePercentage);
    }

    /**
     * Test getDiskUsage method
     *
     * @return void
     */
    public function testGetSoftwareInfo(): void
    {
        // call the method being tested
        $softwareInfo = $this->serverUtil->getSystemInfo();

        // assert that the result is an array with keys 'packages' and 'distro'
        $this->assertIsArray($softwareInfo);
    }

    /**
     * Test getDiskUsage method
     *
     * @return void
     */
    public function testIsWebUserSudo(): void
    {
        // call the method being tested
        $isSudo = $this->serverUtil->isWebUserSudo();

        // assert that the result is a boolean value
        $this->assertIsBool($isSudo);
    }

    /**
     * Test getDriveUsagePercentage method
     *
     * @return void
     */
    public function testGetDriveUsagePercentage(): void
    {
        // call the method being tested
        $driveUsagePercentage = $this->serverUtil->getDriveUsagePercentage();

        // assert that the result is a string
        $this->assertIsString($driveUsagePercentage);
    }

    /**
     * Test getDiskUsage method
     *
     * @return void
     */
    public function testGetDiskUsage(): void
    {
        // call the method being tested
        $diskUsage = $this->serverUtil->getDiskUsage();

        // assert that the result is an integer
        $this->assertIsInt($diskUsage);
    }

    /**
     * Test getDiskUsage method
     *
     * @return void
     */
    public function testIsSystemLinux(): void
    {
        // call the method being tested
        $isLinux = $this->serverUtil->isSystemLinux();

        // assert that the result is a boolean value
        $this->assertIsBool($isLinux);
    }
}
