<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SecurityUtilTest
 *
 * Test cases for security util
 *
 * @package App\Tests\Util
 */
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

        // create the security util instance
        $this->securityUtil = new SecurityUtil(
            new AppUtil($this->jsonUtilMock, $this->kernelInterface)
        );
    }

    /**
     * Test XSS escaping
     *
     * @return void
     */
    public function testEscapeXss(): void
    {
        $input = '<script>alert("XSS");</script>';
        $expectedOutput = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';

        // call the method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test security escaping without XSS
     *
     * @return void
     */
    public function testEscapeNonXss(): void
    {
        $input = 'Hello, World!';
        $expectedOutput = 'Hello, World!';

        // call the method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test generating an Argon2 hash for a password
     *
     * @return void
     */
    public function testGenerateHash(): void
    {
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // assert that the hash is a valid Argon2 hash
        $info = password_get_info($hash);
        $this->assertEquals('argon2id', $info['algoName']);
    }

    /**
     * Test verifying a password using an Argon2 hash
     *
     * @return void
     */
    public function testVerifyPasswordValid(): void
    {
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call the method
        $result = $this->securityUtil->verifyPassword($password, $hash);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test verifying an invalid password using an Argon2 hash
     *
     * @return void
     */
    public function testVerifyPasswordInvalid(): void
    {
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call the method
        $result = $this->securityUtil->verifyPassword('wrongPassword123', $hash);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test encrypt aes
     *
     * @return void
     */
    public function testEncryptAes(): void
    {
        // encrypt string to aes
        $encryptedData = $this->securityUtil->encryptAes('test value');

        // decrypt aes to string
        $decryptedData = $this->securityUtil->decryptAes($encryptedData);

        // assert that the decrypted data is the same as the original data
        $this->assertSame('test value', $decryptedData);
    }
}
