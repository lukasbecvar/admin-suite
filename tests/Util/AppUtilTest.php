<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\JsonUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AppUtilTest
 *
 * Test cases for app util
 *
 * @package App\Tests\Util
 */
class AppUtilTest extends TestCase
{
    private AppUtil $appUtil;
    private JsonUtil & MockObject $jsonUtilMock;
    private KernelInterface & MockObject $kernelInterface;

    protected function setUp(): void
    {
        // mock dependencies
        $this->jsonUtilMock = $this->createMock(JsonUtil::class);
        $this->kernelInterface = $this->createMock(KernelInterface::class);

        // create app util instance
        $this->appUtil = new AppUtil(
            $this->jsonUtilMock,
            $this->kernelInterface
        );
    }

    /**
     * Test get app version
     *
     * @return void
     */
    public function testGetAppRootDir(): void
    {
        // expect call get project dir
        $this->kernelInterface->expects($this->once())
            ->method('getProjectDir');

        // call tested method
        $result = $this->appUtil->getAppRootDir();

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test check if assets exist
     *
     * @return void
     */
    public function testIsAssetsExist(): void
    {
        // call tested method
        $result = $this->appUtil->isAssetsExist();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check if request is secure when https is on
     *
     * @return void
     */
    public function testCheckIfRequestIsSecureWithHttpsWhenHttpsIsOn(): void
    {
        $_SERVER['HTTPS'] = 1;
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->appUtil->isSsl());
    }

    /**
     * Test check if request is secure when https is off
     *
     * @return void
     */
    public function testCheckIfRequestIsSecureWithHttpWhenHttpsIsOff(): void
    {
        $_SERVER['HTTPS'] = 0;
        $this->assertFalse($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse($this->appUtil->isSsl());

        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->appUtil->isSsl());
    }

    /**
     * Test check if dev mode is enabled when dev mode is on
     *
     * @return void
     */
    public function testCheckIfDevModeIsEnabledWhenDevModeIsOn(): void
    {
        // simulate dev mode enabled
        $_ENV['APP_ENV'] = 'dev';

        // call tested method
        $result = $this->appUtil->isDevMode();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if dev mode is disabled when dev mode is off
     *
     * @return void
     */
    public function testCheckIfDevModeIsDisabledWhenDevModeIsOff(): void
    {
        // simulate dev mode disabled
        $_ENV['APP_ENV'] = 'prod';

        // call tested method
        $result = $this->appUtil->isDevMode();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test check if ssl only is enabled when ssl only is on
     *
     * @return void
     */
    public function testCheckIfSslOnlyIsEnabledWhenSslOnlyIsOn(): void
    {
        // simulate ssl only enabled
        $_ENV['SSL_ONLY'] = 'true';

        // call tested method
        $result = $this->appUtil->isSslOnly();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if ssl only is disabled when ssl only is off
     *
     * @return void
     */
    public function testCheckIfSslOnlyIsDisabledWhenSslOnlyIsOff(): void
    {
        // simulate ssl only disabled
        $_ENV['SSL_ONLY'] = 'false';

        // call tested method
        $result = $this->appUtil->isSslOnly();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test check if maintenance mode is enabled when maintenance mode is on
     *
     * @return void
     */
    public function testCheckIfMaintenanceModeIsEnabledWhenMaintenanceModeIsOn(): void
    {
        // simulate maintenance mode enabled
        $_ENV['MAINTENANCE_MODE'] = 'true';

        // call tested method
        $result = $this->appUtil->isMaintenance();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if maintenance mode is disabled when maintenance mode is off
     *
     * @return void
     */
    public function testCheckIfMaintenanceModeIsDisabledWhenMaintenanceModeIsOff(): void
    {
        // simulate maintenance mode disabled
        $_ENV['MAINTENANCE_MODE'] = 'false';

        // call tested method
        $result = $this->appUtil->isMaintenance();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test check if database logging is enabled when database logging is on
     *
     * @return void
     */
    public function testCheckIfDatabaseLoggingIsEnabledWhenDatabaseLoggingIsOn(): void
    {
        // simulate database logging enabled
        $_ENV['DATABASE_LOGGING'] = 'true';

        // call tested method
        $result = $this->appUtil->isDatabaseLoggingEnabled();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check if database logging is disabled when database logging is off
     *
     * @return void
     */
    public function testCheckIfDatabaseLoggingIsDisabledWhenDatabaseLoggingIsOff(): void
    {
        // simulate database logging disabled
        $_ENV['DATABASE_LOGGING'] = 'false';

        // call tested method
        $result = $this->appUtil->isDatabaseLoggingEnabled();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test get environment variable value
     *
     * @return void
     */
    public function testGetEnvValue(): void
    {
        // set env value
        $_ENV['TEST_KEY'] = 'test-value';

        // call tested method
        $result = $this->appUtil->getEnvValue('TEST_KEY');

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test get hasher config
     *
     * @return void
     */
    public function testGetHasherConfig(): void
    {
        // set env values
        $_ENV['MEMORY_COST'] = '1024';
        $_ENV['TIME_COST'] = '2';
        $_ENV['THREADS'] = '1';

        // expected config
        $expectedConfig = [
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 1,
        ];

        // call tested method
        $result = $this->appUtil->getHasherConfig();

        // assert result
        $this->assertIsArray($result);
        $this->assertSame($expectedConfig, $result);
    }

    /**
     * Test load config file
     *
     * @return void
     */
    public function testLoadConfig(): void
    {
        // expect getJson method call
        $this->jsonUtilMock->expects($this->once())->method('getJson');

        // call tested method
        $this->appUtil->loadConfig('services-monitoring.json');
    }

    /**
     * Test calculate max pages
     *
     * @return void
     */
    public function testCalculateMaxPages(): void
    {
        // call tested method
        $maxPages = $this->appUtil->calculateMaxPages(100, 10);

        // assert result
        $this->assertIsNumeric($maxPages);
        $this->assertSame(10, (int) $maxPages);
    }

    /**
     * Test round times in array
     *
     * @return void
     */
    public function testRoundTimesInArray(): void
    {
        // input data
        $testData = [
            '13:15',
            '13:45',
            '23:30',
            '2023-01-01 13:15',
            '2023-01-01 13:45',
            '2023-01-01 23:30',
            'invalid time',
        ];

        // expected results
        $expectedResults = [
            '13:00',
            '14:00',
            '00:00',
            '2023-01-01 13:00',
            '2023-01-01 14:00',
            '2023-01-01 00:00',
            'invalid time',
        ];

        // call tested method
        $result = $this->appUtil->roundTimesInArray($testData);

        // assert results
        $this->assertSame($expectedResults, $result);
    }
}
