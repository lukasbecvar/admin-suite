<?php

namespace App\Tests\Util;

use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    public function testStartSession(): void
    {
        // Ensure that the session is not started and headers are not sent
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Start the session and check the status
        $this->sessionUtil->startSession();

        $this->assertEquals(1, session_status());
    }

    public function testDestroySession(): void
    {
        $this->sessionUtil->startSession();
        $this->sessionUtil->destroySession();
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testCheckSession(): void
    {
        $this->sessionUtil->startSession();
        $_SESSION['test'] = 'value';
        $this->assertTrue($this->sessionUtil->checkSession('test'));
        $this->assertFalse($this->sessionUtil->checkSession('nonexistent'));
    }

    public function testSetSession(): void
    {
        $sessionName = 'test';
        $sessionValue = 'value';
        $encryptedValue = 'encrypted_value';

        $this->securityUtilMock->expects($this->once())
            ->method('encryptAes')
            ->with($sessionValue)
            ->willReturn($encryptedValue);

        $this->sessionUtil->setSession($sessionName, $sessionValue);
        $this->assertEquals($encryptedValue, $_SESSION[$sessionName]);
    }

    public function testGetSessionValue(): void
    {
        $sessionName = 'test';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'value';

        $this->sessionUtil->startSession();
        $_SESSION[$sessionName] = $encryptedValue;

        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn($decryptedValue);

        $value = $this->sessionUtil->getSessionValue($sessionName);
        $this->assertEquals($decryptedValue, $value);
    }

    public function testGetSessionValueDecryptionFailure(): void
    {
        $sessionName = 'test';
        $encryptedValue = 'encrypted_value';

        $this->sessionUtil->startSession();
        $_SESSION[$sessionName] = $encryptedValue;

        $this->securityUtilMock->expects($this->once())
            ->method('decryptAes')
            ->with($encryptedValue)
            ->willReturn(null);

        $this->errorManagerMock->expects($this->once())
            ->method('handleError')
            ->with('Error to decrypt session data', 500);

        $this->sessionUtil->getSessionValue($sessionName);
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }
}
