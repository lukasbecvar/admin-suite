<?php

namespace App\Tests\Util;

use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class SessionUtilTest
 *
 * Test the SessionUtil class
 *
 * @package App\Tests\Util
 */
class SessionUtilTest extends TestCase
{
    /** @var SecurityUtil|MockObject */
    private SecurityUtil|MockObject $securityUtilMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var SessionUtil */
    private SessionUtil $sessionUtil;

    protected function setUp(): void
    {
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        $this->sessionUtil = new SessionUtil($this->securityUtilMock, $this->errorManagerMock);
    }

    /**
     * Test the startSession method
     *
     * @return void
     */
    public function testStartSession(): void
    {
        // ensure that the session is not started and headers are not sent
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // start the session and check the status
        $this->sessionUtil->startSession();

        // assert that the session was started
        $this->assertEquals(1, session_status());
    }

    /**
     * Test the setSession method
     *
     * @return void
     */
    public function testDestroySession(): void
    {
        // start the session
        $this->sessionUtil->startSession();

        // destroy the session
        $this->sessionUtil->destroySession();

        // assert that the session was destroyed
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    /**
     * Test the checkSession method
     *
     * @return void
     */
    public function testCheckSession(): void
    {
        // start the session
        $this->sessionUtil->startSession();

        // set a session value
        $_SESSION['test'] = 'value';

        // assert session exists
        $this->assertTrue($this->sessionUtil->checkSession('test'));
        $this->assertFalse($this->sessionUtil->checkSession('nonexistent'));
    }

    /**
     * Test the setSession method
     *
     * @return void
     */
    public function testSetSession(): void
    {
        // session values
        $sessionName = 'test';
        $sessionValue = 'value';
        $encryptedValue = 'encrypted_value';

        // mock the encryptAes method
        $this->securityUtilMock->expects($this->once())
            ->method('encryptAes')
            ->with($sessionValue)
            ->willReturn($encryptedValue);

        // set the session
        $this->sessionUtil->setSession($sessionName, $sessionValue);

        // assert that the session was set
        $this->assertEquals($encryptedValue, $_SESSION[$sessionName]);
    }

    /**
     * Test the getSession method
     *
     * @return void
     */
    public function testGetSessionValue(): void
    {
        // session values
        $sessionName = 'test';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'value';

        // start the session
        $this->sessionUtil->startSession();

        // set session value
        $_SESSION[$sessionName] = $encryptedValue;

        // mock the decryptAes method
        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn($decryptedValue);

        // get the session value
        $value = $this->sessionUtil->getSessionValue($sessionName);

        // assert that the session was set
        $this->assertEquals($decryptedValue, $value);
    }

    /**
     * Test the session decryption failure.
     *
     * @return void
     */
    public function testGetSessionValueDecryptionFailure(): void
    {
        // session values
        $sessionName = 'test';
        $encryptedValue = 'encrypted_value';

        // start the session
        $this->sessionUtil->startSession();

        // set session value
        $_SESSION[$sessionName] = $encryptedValue;

        // mock the decryptAes method
        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn(null);

        // mock the error manager
        $this->errorManagerMock->expects($this->once())
            ->method('handleError')
            ->with('error to decrypt session data', 500);

        // get the session value
        $this->sessionUtil->getSessionValue($sessionName);

        // assert that the session not runing
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }
}
