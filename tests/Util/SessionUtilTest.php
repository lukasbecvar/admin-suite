<?php

namespace App\Tests\Util;

use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class SessionUtilTest
 *
 * Test cases for session management util
 *
 * @package App\Tests\Util
 */
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

        // mock request stack to return the session
        $this->requestStackMock->method('getSession')->willReturn($this->sessionInterfaceMock);

        // create instance of SessionUtil
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
        $this->sessionInterfaceMock->expects($this->once())->method('start');

        // call method to test
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
        $this->sessionInterfaceMock->expects($this->never())->method('start');

        // call method to test
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
        $this->sessionInterfaceMock->expects($this->once())->method('invalidate');

        // call method to test
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
        $this->sessionInterfaceMock->expects($this->never())->method('invalidate');

        // call method to test
        $this->sessionUtil->destroySession();
    }

    /**
     * Test check session value
     *
     * @return void
     */
    public function testCheckSessionTrue(): void
    {
        // simulate session with specific name
        $this->sessionInterfaceMock->method('has')->with('testing-value')->willReturn(true);

        // call method to test
        $result = $this->sessionUtil->checkSession('testing-value');
        $this->assertTrue($result);
    }

    /**
     * Test check session value
     *
     * @return void
     */
    public function testCheckSessionFalse(): void
    {
        // simulate session without specific name
        $this->sessionInterfaceMock->method('has')->with('testing-value')->willReturn(false);

        // call method to test
        $result = $this->sessionUtil->checkSession('testing-value');
        $this->assertFalse($result);
    }

    /**
     * Test set session value
     *
     * @return void
     */
    public function testSetSession(): void
    {
        $sessionName = 'testSession';
        $sessionValue = 'testValue';
        $encryptedValue = 'encryptedTestValue';

        // mock encryption
        $this->securityUtilMock->method('encryptAes')->with($sessionValue)->willReturn($encryptedValue);

        // expect session to set the encrypted value
        $this->sessionInterfaceMock->expects($this->once())->method('set')->with($sessionName, $encryptedValue);

        // call method to test
        $this->sessionUtil->setSession($sessionName, $sessionValue);
    }

    /**
     * Test get session value when valid
     *
     * @return void
     */
    public function testGetSessionValueWhenValid(): void
    {
        $sessionName = 'testSession';
        $encryptedValue = 'encryptedTestValue';
        $decryptedValue = 'testValue';

        // mock decryption
        $this->securityUtilMock->method('decryptAes')->with($encryptedValue)->willReturn($decryptedValue);

        // mock session get
        $this->sessionInterfaceMock->method('get')->with($sessionName)->willReturn($encryptedValue);

        // call method to test
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert that the session was set
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
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call method to test
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert null result
        $this->assertNull($result);
    }
}
