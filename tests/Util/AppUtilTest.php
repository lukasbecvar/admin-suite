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

    public function setUp(): void
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
        $this->assertSame($limit, (int) $_ENV['LIMIT_PER_PAGE']);
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
}
