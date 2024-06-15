<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class AppUtilTest
 *
 * Test the app util
 *
 * @package App\Tests\Util
 */
class AppUtilTest extends TestCase
{
    private AppUtil $appUtil;

    protected function setUp(): void
    {
        $this->appUtil = new AppUtil();
    }

    /**
     * Test is SSL
     *
     * @return void
     */
    public function testIsSsl(): void
    {
        $_SERVER['HTTPS'] = 1;
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 0;
        $this->assertFalse($this->appUtil->isSsl());

        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->appUtil->isSsl());
    }

    /**
     * Test assets exist
     *
     * @return void
     */
    public function testIsAssetsExist(): void
    {
        $this->assertIsBool($this->appUtil->isAssetsExist());
    }

    /**
     * Test dev mode check
     *
     * @return void
     */
    public function testIsDevMode(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->assertTrue($this->appUtil->isDevMode());

        $_ENV['APP_ENV'] = 'test';
        $this->assertTrue($this->appUtil->isDevMode());

        $_ENV['APP_ENV'] = 'prod';
        $this->assertFalse($this->appUtil->isDevMode());
    }

    /**
     * Test SSl only check
     *
     * @return void
     */
    public function testIsSslOnly(): void
    {
        $_ENV['SSL_ONLY'] = 'true';
        $this->assertTrue($this->appUtil->isSSLOnly());

        $_ENV['SSL_ONLY'] = 'false';
        $this->assertFalse($this->appUtil->isSSLOnly());
    }

    /**
     * Test maintenance check
     *
     * @return void
     */
    public function testIsMaintenance(): void
    {
        $_ENV['MAINTENANCE_MODE'] = 'true';
        $this->assertTrue($this->appUtil->isMaintenance());

        $_ENV['MAINTENANCE_MODE'] = 'false';
        $this->assertFalse($this->appUtil->isMaintenance());
    }

    /**
     * Test loging enabled check
     *
     * @return void
     */
    public function testIsDatabaseLoggingEnabled(): void
    {
        $_ENV['DATABASE_LOGGING'] = 'true';
        $this->assertTrue($this->appUtil->isDatabaseLoggingEnabled());

        $_ENV['DATABASE_LOGGING'] = 'false';
        $this->assertFalse($this->appUtil->isDatabaseLoggingEnabled());
    }

    /**
     * Test get log level
     *
     * @return void
     */
    public function testGetLogLevel(): void
    {
        $_ENV['LOG_LEVEL'] = '1';
        $this->assertSame(1, $this->appUtil->getLogLevel());

        $_ENV['LOG_LEVEL'] = '2';
        $this->assertSame(2, $this->appUtil->getLogLevel());
    }

    /**
     * Test get hasher config
     *
     * @return void
     */
    public function testPageLimitter(): void
    {
        // get page limitter
        $limit = $this->appUtil->getPageLimitter();

        // check if limit is valid
        $this->assertSame($limit, (int) $_ENV['limitPerPage']);
    }

    /**
     * Test calculateMaxPages method
     *
     * @return void
     */
    public function testCalculateMaxPages(): void
    {
        // calculate max pages
        $maxPages = (int) $this->appUtil->calculateMaxPages(100, 10);

        // check if max pages is valid
        $this->assertSame($maxPages, 10);
    }

    /**
     * Test getAdminContactEmail
     *
     * @return void
     */
    public function testGetAdminContactEmail(): void
    {
        $_ENV['ADMIN_CONTACT'] = 'admin@example.com';
        $this->assertSame('admin@example.com', $this->appUtil->getAdminContactEmail());

        $_ENV['ADMIN_CONTACT'] = 'support@example.com';
        $this->assertSame('support@example.com', $this->appUtil->getAdminContactEmail());
    }

    /**
     * Test getAntiLogToken
     *
     * @return void
     */
    public function testGetAntiLogToken(): void
    {
        $_ENV['ANTI_LOG_TOKEN'] = 'secret-token';
        $this->assertSame('secret-token', $this->appUtil->getAntiLogToken());

        $_ENV['ANTI_LOG_TOKEN'] = 'another-token';
        $this->assertSame('another-token', $this->appUtil->getAntiLogToken());
    }

    /**
     * Test getHasherConfig
     *
     * @return void
     */
    public function testGetHasherConfig(): void
    {
        $_ENV['MEMORY_COST'] = '1024';
        $_ENV['TIME_COST'] = '2';
        $_ENV['THREADS'] = '1';

        $expectedConfig = [
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 1,
        ];

        $this->assertSame($expectedConfig, $this->appUtil->getHasherConfig());

        $_ENV['MEMORY_COST'] = '2048';
        $_ENV['TIME_COST'] = '4';
        $_ENV['THREADS'] = '2';

        $expectedConfig = [
            'memory_cost' => 2048,
            'time_cost' => 4,
            'threads' => 2,
        ];

        $this->assertSame($expectedConfig, $this->appUtil->getHasherConfig());
    }
}
