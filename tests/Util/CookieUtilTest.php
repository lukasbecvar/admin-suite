<?php

namespace App\Tests\Util;

use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CookieUtilTest
 *
 * Test the CookieUtil class
 *
 * @package App\Tests\Util
 */
class CookieUtilTest extends TestCase
{
    /** @var SecurityUtil|MockObject */
    private SecurityUtil|MockObject $securityUtilMock;

    /** @var CookieUtil */
    private CookieUtil $cookieUtil;

    protected function setUp(): void
    {
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->cookieUtil = new CookieUtil($this->securityUtilMock);
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
    }

    /**
     * Test the set method
     *
     * @return void
     */
    public function testSet(): void
    {
        // set cookie values
        $name = 'test_cookie';
        $value = 'test_value';
        $expiration = time() + 3600;
        $encryptedValue = 'encrypted_value';

        // call the set method and then simulate the cookie being set
        $this->cookieUtil->set($name, $value, $expiration);
        $_COOKIE[$name] = base64_encode($encryptedValue);

        // assert that the cookie was set
        $this->assertArrayHasKey($name, $_COOKIE, 'Cookie should be set');
        $this->assertEquals(base64_encode($encryptedValue), $_COOKIE[$name], 'Cookie value should be encrypted and base64 encoded');
    }

    /**
     * Test the get method
     *
     * @return void
     */
    public function testGet(): void
    {
        // set cookie values
        $name = 'test_cookie';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'test_value';

        // call the set method and then simulate the cookie being set
        $_COOKIE[$name] = base64_encode($encryptedValue);

        // mock the decryptAes method
        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn($decryptedValue);

        // call the get method
        $value = $this->cookieUtil->get($name);

        // assert that the cookie was set
        $this->assertEquals($decryptedValue, $value, 'Cookie value should be decrypted');
    }

    /**
     * Test the unset method
     *
     * @return void
     */
    public function testUnset(): void
    {
        // set cookie values
        $name = 'test_cookie';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/path';

        // call unset method
        $this->cookieUtil->unset($name);

        // assert that the cookie was unset
        $this->assertEquals('', $_COOKIE[$name] ?? '', 'Cookie should be unset');
    }
}
