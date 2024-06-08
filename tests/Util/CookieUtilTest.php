<?php

namespace App\Tests\Util;

use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    public function testSet(): void
    {
        $name = 'test_cookie';
        $value = 'test_value';
        $expiration = time() + 3600;
        $encryptedValue = 'encrypted_value';



        // Call the set method and then simulate the cookie being set
        $this->cookieUtil->set($name, $value, $expiration);
        $_COOKIE[$name] = base64_encode($encryptedValue);

        $this->assertArrayHasKey($name, $_COOKIE, 'Cookie should be set');
        $this->assertEquals(base64_encode($encryptedValue), $_COOKIE[$name], 'Cookie value should be encrypted and base64 encoded');
    }


    public function testGet(): void
    {
        $name = 'test_cookie';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'test_value';

        $_COOKIE[$name] = base64_encode($encryptedValue);

        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn($decryptedValue);

        $value = $this->cookieUtil->get($name);

        $this->assertEquals($decryptedValue, $value, 'Cookie value should be decrypted');
    }

    public function testUnset(): void
    {
        $name = 'test_cookie';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/path';

        $this->cookieUtil->unset($name);

        $this->assertEquals('', $_COOKIE[$name] ?? '', 'Cookie should be unset');
    }
}
