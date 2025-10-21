<?php

namespace App\Tests\Util;

use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class SessionUtilTest
 *
 * Test cases for session util
 *
 * @package App\Tests\Util
 */
#[CoversClass(SessionUtil::class)]
class SessionUtilTest extends TestCase
{
    private SessionUtil $sessionUtil;
    private RequestStack & MockObject $requestStackMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private SessionInterface & MockObject $sessionInterfaceMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->sessionInterfaceMock = $this->createMock(SessionInterface::class);

        // mock request stack for session get
        $this->requestStackMock->method('getSession')->willReturn($this->sessionInterfaceMock);

        // create session util instance
        $this->sessionUtil = new SessionUtil(
            $this->requestStackMock,
            $this->securityUtilMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test start session when not started
     *
     * @return void
     */
    public function testStartSessionWhenNotStarted(): void
    {
        // simulate session not started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);

        // expect session start call
        $this->sessionInterfaceMock->expects($this->once())->method('start');

        // call tested method
        $this->sessionUtil->startSession();
    }

    /**
     * Test start session when already started
     *
     * @return void
     */
    public function testStartSessionWhenAlreadyStarted(): void
    {
        // simulate session already started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(true);

        // expect session start not to be called
        $this->sessionInterfaceMock->expects($this->never())->method('start');

        // call tested method
        $this->sessionUtil->startSession();
    }

    /**
     * Test destroy session when started
     *
     * @return void
     */
    public function testDestroySessionWhenStarted(): void
    {
        // simulate session started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(true);

        // expect session invalidate call
        $this->sessionInterfaceMock->expects($this->once())->method('invalidate');

        // call tested method
        $this->sessionUtil->destroySession();
    }

    /**
     * Test destroy session when not started
     *
     * @return void
     */
    public function testDestroySessionWhenNotStarted(): void
    {
        // simulate session not started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);

        // expect session invalidate not to be called
        $this->sessionInterfaceMock->expects($this->never())->method('invalidate');

        // call tested method
        $this->sessionUtil->destroySession();
    }

    /**
     * Test check if session exists when value set
     *
     * @return void
     */
    public function testCheckSessionExistsWhenValueSet(): void
    {
        // simulate session with specific name
        $this->sessionInterfaceMock->method('has')->with('testing-value')->willReturn(true);

        // call tested method
        $result = $this->sessionUtil->checkSession('testing-value');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if session exists when value not set
     *
     * @return void
     */
    public function testCheckSessionDoesNotExistWhenValueNotSet(): void
    {
        // simulate session without specific name
        $this->sessionInterfaceMock->method('has')->with('testing-value')->willReturn(false);

        // call tested method
        $result = $this->sessionUtil->checkSession('testing-value');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test save value to session storage
     *
     * @return void
     */
    public function testSaveValueToSessionStorage(): void
    {
        $sessionName = 'testSession';
        $sessionValue = 'testValue';
        $encryptedValue = 'encryptedTestValue';

        // mock encryption
        $this->securityUtilMock->method('encryptAes')->with($sessionValue)->willReturn($encryptedValue);

        // expect session to set call
        $this->sessionInterfaceMock->expects($this->once())->method('set')->with($sessionName, $encryptedValue);

        // call tested method
        $this->sessionUtil->setSession($sessionName, $sessionValue);
    }

    /**
     * Test get session value when session is valid
     *
     * @return void
     */
    public function testGetSessionValueWhenSessionIsValid(): void
    {
        $sessionName = 'testSession';
        $encryptedValue = 'encryptedTestValue';
        $decryptedValue = 'testValue';

        // mock decryption
        $this->securityUtilMock->method('decryptAes')->with($encryptedValue)->willReturn($decryptedValue);

        // mock session get
        $this->sessionInterfaceMock->method('get')->with($sessionName)->willReturn($encryptedValue);

        // call tested method
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert result
        $this->assertEquals($decryptedValue, $result);
    }

    /**
     * Test get session value when decryption fails
     *
     * @return void
     */
    public function testGetSessionValueWhenDecryptionFails(): void
    {
        $sessionName = 'testSession';
        $encryptedValue = 'encryptedTestValue';

        // mock decryption failure (null result)
        $this->securityUtilMock->method('decryptAes')->with($encryptedValue)->willReturn(null);

        // mock session get
        $this->sessionInterfaceMock->method('get')->with($sessionName)->willReturn($encryptedValue);

        // expect error handling to be called
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to decrypt session data',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get session id
     *
     * @return void
     */
    public function testGetSessionId(): void
    {
        // call tested method
        $result = $this->sessionUtil->getSessionId();

        // assert result
        $this->assertIsString($result);
    }
}
