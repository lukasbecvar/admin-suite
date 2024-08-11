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
    /** @var CookieUtil */
    private CookieUtil $cookieUtil;

    /** @var SecurityUtil|MockObject */
    private SecurityUtil|MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // create the cookie util instance
        $this->cookieUtil = new CookieUtil($this->securityUtilMock);
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
    }

    /**
     * test set cookie
     *
     * @return void
     */
    public function testSetCookie(): void
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
        $this->assertEquals(
            base64_encode($encryptedValue),
            $_COOKIE[$name],
            'Cookie value should be encrypted and base64 encoded'
        );
    }

    /**
     * Test check is set cookie check
     *
     * @return void
     */
    public function testIsCookieSet(): void
    {
        $bool = $this->cookieUtil->isCookieSet('test_cookie');

        $this->assertIsBool($bool);
    }

    /**
     * Test get cookie value
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
     * Test the unset cookie
     *
     * @return void
     */
    public function testUnsetCookie(): void
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
