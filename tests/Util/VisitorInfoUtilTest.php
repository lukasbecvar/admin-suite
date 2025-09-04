<?php

namespace App\Tests\Util;

use App\Util\CacheUtil;
use App\Util\SecurityUtil;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface $loggerMock;
    private VisitorInfoUtil $visitorInfoUtil;
    private CacheUtil & MockObject $cacheUtilMock;
    private SecurityUtil & MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock escape string behavior
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function ($string) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
        });

        // create visitor info util instance
        $this->visitorInfoUtil = new VisitorInfoUtil($this->cacheUtilMock, $this->loggerMock, $this->securityUtilMock);
    }

    /**
     * Test get visitor ip when HTTP_CLIENT_IP header is set
     *
     * @return void
     */
    public function testGetIpWhenHttpClientIpHeaderIsSet(): void
    {
        // set server variables
        $_SERVER['HTTP_CLIENT_IP'] = '192.168.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.2';

        // call tested method
        $result = $this->visitorInfoUtil->getIP();

        // assert result
        $this->assertEquals('192.168.0.1', $result);

        // unset server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get visitor ip when HTTP_X_FORWARDED_FOR header is set
     *
     * @return void
     */
    public function testGetIpWhenHttpXForwardedForHeaderIsSet(): void
    {
        // set server variables
        $_SERVER['HTTP_CLIENT_IP'] = '';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.0.3';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.4';

        // call tested method
        $result = $this->visitorInfoUtil->getIP();

        // assert result
        $this->assertEquals('192.168.0.3', $result);

        // unset server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get visitor ip when REMOTE_ADDR header is set
     *
     * @return void
     */
    public function testGetIpWhenRemoteAddrHeaderIsSet(): void
    {
        // set server variables
        $_SERVER['HTTP_CLIENT_IP'] = '';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['REMOTE_ADDR'] = '192.168.0.5';

        // call tested method
        $result = $this->visitorInfoUtil->getIP();

        // assert result
        $this->assertEquals('192.168.0.5', $result);

        // unset server variables
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Test get user agent when HTTP_USER_AGENT header is set
     *
     * @return void
     */
    public function testGetUserAgentWhenHttpUserAgentHeaderIsSet(): void
    {
        // set server variable
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

        // call tested method
        $result = $this->visitorInfoUtil->getUserAgent();

        // assert result
        $this->assertEquals('Mozilla/5.0', $result);

        // unset server variable
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Test get user agent when HTTP_USER_AGENT header is not set
     *
     * @return void
     */
    public function testGetUserAgentWhenHttpUserAgentHeaderIsNotSet(): void
    {
        // unset server variable
        unset($_SERVER['HTTP_USER_AGENT']);

        // call tested method
        $result = $this->visitorInfoUtil->getUserAgent();

        // assert result
        $this->assertEquals('Unknown', $result);
    }

    /**
     * Test get shortified browser name when user agent is chrome
     *
     * @return void
     */
    public function testGetShortifiedBrowserName(): void
    {
        // call tested method
        $result = $this->visitorInfoUtil->getBrowserShortify(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.9999.999 Safari/537.36'
        );

        // assert result
        $this->assertEquals('Chrome', $result);
    }

    /**
     * Test get shortified browser name when user agent is unknown
     *
     * @return void
     */
    public function testGetShortifiedBrowserNameWhenUserAgentIsUnknown(): void
    {
        // call tested method
        $result = $this->visitorInfoUtil->getBrowserShortify('Browser bla bla bla bla');

        // assert result
        $this->assertEquals('Unknown', $result);
    }

    /**
     * Test get visitor os name
     *
     * @return void
     */
    public function testGetOsName(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.9999.999 Safari/537.36';

        // call tested method
        $result = $this->visitorInfoUtil->getOs();

        // assert result
        $this->assertEquals('Windows 10', $result);
    }

    /**
     * Test get visitor ip info
     *
     * @return void
     */
    public function testGetIpInfo(): void
    {
        // assert result
        $this->assertNotNull($this->visitorInfoUtil->getIpInfo('8.8.8.8'));
    }
}
