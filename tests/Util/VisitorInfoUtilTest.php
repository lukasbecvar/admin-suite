<?php

namespace App\Tests\Util;

use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class VisitorInfoUtilTest
 *
 * Test cases for visitor info util
 *
 * @package App\Tests\Util
 */
class VisitorInfoUtilTest extends TestCase
{
    private VisitorInfoUtil $visitorInfoUtil;
    private SecurityUtil & MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock escape string behavior
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function ($string) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
        });

        // create visitor info util instance
        $this->visitorInfoUtil = new VisitorInfoUtil($this->securityUtilMock);
    }

    /**
     * Test get visitor ip when the HTTP_CLIENT_IP server variable is set
     *
     * @return void
     */
    public function testGetIpWithClientIp(): void
    {
        // set the server variables
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.2';

        // assert IP is '
        $this->assertEquals('192.168.0.1', $this->visitorInfoUtil->getIP());

        // unset the server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get visitor ip when the HTTP_X_FORWARDED_FOR server variable is set
     *
     * @return void
     */
    public function testGetIpWithForwardedFor(): void
    {
        // set the server variables
        $_SERVER['HTTP_CLIENT_IP'] = '';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.0.3';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.4';

        // assert IP is '
        $this->assertEquals('192.168.0.3', $this->visitorInfoUtil->getIP());

        // unset the server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get visitor ip when the REMOTE_ADDR server variable is set
     *
     * @return void
     */
    public function testGetIpWithRemoteAddr(): void
    {
        // set the server variables
        $_SERVER['HTTP_CLIENT_IP'] = '';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.5';

        // assert IP is '
        $this->assertEquals('192.168.0.5', $this->visitorInfoUtil->getIP());

        // unset the server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get user agent when the HTTP_USER_AGENT server variable is set
     *
     * @return void
     */
    public function testGetUserAgentWithUserAgent(): void
    {
        // set the server variable
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

        // assert user agent is 'Mozilla/5.0' when the server variable is set
        $this->assertEquals('Mozilla/5.0', $this->visitorInfoUtil->getUserAgent());

        // unset the server variable
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test get user agent when the HTTP_USER_AGENT server variable is not set
     *
     * @return void
     */
    public function testGetUserAgentWithNoUserAgent(): void
    {
        // unset the server variable
        unset($_SERVER['HTTP_USER_AGENT']);

        // assert user agent is 'Unknown' when the server variable is not set
        $this->assertEquals('Unknown', $this->visitorInfoUtil->getUserAgent());
    }

    /**
     * Test get browser method for getting the visitor's browser
     *
     * @return void
     */
    public function testGetBrowserShortify(): void
    {
        $shortifyBrowser = $this->visitorInfoUtil->getBrowserShortify(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.9999.999 Safari/537.36'
        );

        $unknownResult = $this->visitorInfoUtil->getBrowserShortify('Browser bla bla bla bla');

        // assert browser result
        $this->assertEquals('Chrome', $shortifyBrowser);
        $this->assertEquals('Unknown', $unknownResult);
    }

    /**
     * Test get visitor os for getting the visitor's operating system
     *
     * @return void
     */
    public function testGetOs(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.9999.999 Safari/537.36';

        // assert Windows OS
        $this->assertEquals('Windows 10', $this->visitorInfoUtil->getOS());
    }
}
