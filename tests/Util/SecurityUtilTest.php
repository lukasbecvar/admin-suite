<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SecurityUtilTest
 *
 * Test cases for security util
 *
 * @package App\Tests\Util
 */
#[CoversClass(SecurityUtil::class)]
class SecurityUtilTest extends TestCase
{
    private SecurityUtil $securityUtil;
    private JsonUtil & MockObject $jsonUtilMock;
    private KernelInterface & MockObject $kernelInterface;

    protected function setUp(): void
    {
        // mock dependencies
        $this->jsonUtilMock = $this->createMock(JsonUtil::class);
        $this->kernelInterface = $this->createMock(KernelInterface::class);

        // create security util instance
        $this->securityUtil = new SecurityUtil(
            new AppUtil($this->jsonUtilMock, $this->kernelInterface)
        );
    }

    /**
     * Test escape XSS in string when string is insecure
     *
     * @return void
     */
    public function testEscapeXssInStringWhenStringIsInsecure(): void
    {
        // arrange test data
        $input = '<script>alert("XSS");</script>';
        $expectedOutput = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';

        // call tested method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test escape XSS in string when string is secure
     *
     * @return void
     */
    public function testEscapeXssInStringWhenStringIsSecure(): void
    {
        $input = 'Hello, World!';
        $expectedOutput = 'Hello, World!';

        // call the method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test generating Argon2 hash
     *
     * @return void
     */
    public function testGenerateHash(): void
    {
        // call tested method
        $result = $this->securityUtil->generateHash('testPassword123');

        // get result info
        $info = password_get_info($result);

        // assert result
        $this->assertEquals('argon2id', $info['algoName']);
    }

    /**
     * Test verifying password when password is valid
     *
     * @return void
     */
    public function testVerifyPasswordWhenPasswordIsValid(): void
    {
        // generate hash
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call tested method
        $result = $this->securityUtil->verifyPassword($password, $hash);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test verifying invalid when password is invalid
     *
     * @return void
     */
    public function testVerifyPasswordWhenPasswordIsInvalid(): void
    {
        // generate hash
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call tested method
        $result = $this->securityUtil->verifyPassword('wrongPassword123', $hash);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test encrypting string to AES
     *
     * @return void
     */
    public function testEncryptStringToAes(): void
    {
        // encrypt string to aes
        $encryptedData = $this->securityUtil->encryptAes('test value');

        // decrypt string from aes
        $decryptedData = $this->securityUtil->decryptAes($encryptedData);

        // assert result
        $this->assertSame('test value', $decryptedData);
    }
}
