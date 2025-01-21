<?php

namespace App\Tests\Util;

use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CookieUtilTest
 *
 * Test cases for CookieUtil class
 *
 * @package App\Tests\Util
 */
class CookieUtilTest extends TestCase
{
    private CookieUtil $cookieUtil;
    private SecurityUtil & MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // create the cookie util instance
        $this->cookieUtil = new CookieUtil($this->securityUtilMock);
    }

    /**
     * Test check is cookie set
     *
     * @return void
     */
    public function testCheckIsCookieSet(): void
    {
        // call tested method
        $value = $this->cookieUtil->isCookieSet('test_cookie');

        // assert response
        $this->assertIsBool($value);
    }

    /**
     * Test get value from cookie
     *
     * @return void
     */
    public function testGetValueFromCookie(): void
    {
        // set cookie values
        $name = 'test_cookie';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'test_value';

        // set value to cookie
        $_COOKIE[$name] = base64_encode($encryptedValue);

        // mock value decryption
        $this->securityUtilMock->expects($this->once())->method('decryptAes')
            ->with($encryptedValue)->willReturn($decryptedValue);

        // call tested method
        $value = $this->cookieUtil->get($name);

        // assert result
        $this->assertEquals($decryptedValue, $value);
    }
}
