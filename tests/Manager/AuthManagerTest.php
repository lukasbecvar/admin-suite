<?php

namespace App\Tests\Manager;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\UserManager;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use App\Manager\LogManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class AuthManagerTest extends TestCase
{
    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var CookieUtil|MockObject */
    private CookieUtil|MockObject $cookieUtilMock;

    /** @var SessionUtil|MockObject */
    private SessionUtil|MockObject $sessionUtilMock;

    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManagerMock;

    /** @var ErrorManager */
    private ErrorManager $errorManagerMock;

    /** @var SecurityUtil|MockObject */
    private SecurityUtil|MockObject $securityUtilMock;

    /** @var VisitorInfoUtil|MockObject */
    private VisitorInfoUtil|MockObject $visitorInfoUtilMock;

    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    /** @var AuthManager */
    private AuthManager $authManager;

    protected function setUp(): void
    {
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->authManager = new AuthManager(
            $this->logManagerMock,
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->userManagerMock,
            $this->errorManagerMock,
            $this->securityUtilMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    public function testRegisterUser(): void
    {
        $this->userManagerMock->method('checkIfUserExist')->willReturn(false);
        $this->userManagerMock->method('getUserRepo')->willReturn(null);
        $this->securityUtilMock->method('generateHash')->willReturn('hashed_password');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Test User Agent');
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');
        $this->logManagerMock->expects($this->once())->method('log');

        $this->authManager->registerUser('test_user', 'test_password');
    }

    public function testIsUserLogedin(): void
    {
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');
        $this->userManagerMock->method('getUserRepo')->willReturn(new User());

        $result = $this->authManager->isUserLogedin();
        $this->assertTrue($result);
    }

    public function testCanLogin(): void
    {
        $user = new User();
        $user->setPassword('hashed_password');

        $this->userManagerMock->method('getUserRepo')->willReturn($user);
        $this->securityUtilMock->method('verifyPassword')->willReturn(true);

        $result = $this->authManager->canLogin('test_user', 'test_password');
        $this->assertTrue($result);
    }

    public function testLogin(): void
    {
        $user = new User();
        $user->setToken('test_token');

        $this->userManagerMock->method('getUserRepo')->willReturn($user);
        $this->sessionUtilMock->expects($this->once())->method('setSession');
        $this->cookieUtilMock->expects($this->once())->method('set');
        $this->entityManagerMock->expects($this->once())->method('flush');
        $this->logManagerMock->expects($this->once())->method('log');

        $this->authManager->login('test_user', true);
    }

    public function testUpdateDataOnLogin(): void
    {
        $user = new User();

        $this->userManagerMock->method('getUserRepo')->willReturn($user);
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Test User Agent');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->authManager->updateDataOnLogin('test_token');
    }

    public function testGetLoggedUserId(): void
    {
        $user = new User();
        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 1);

        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');
        $this->userManagerMock->method('getUserRepo')->willReturn($user);

        $result = $this->authManager->getLoggedUserId();
        $this->assertEquals(1, $result);
    }
    public function testGetLoggedUserToken(): void
    {
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');
        $this->userManagerMock->method('getUserRepo')->willReturn(new User());

        $result = $this->authManager->getLoggedUserToken();
        $this->assertEquals('test_token', $result);
    }

    public function testLogout(): void
    {
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');
        $this->userManagerMock->method('getUserRepo')->willReturn(new User());
        $this->cookieUtilMock->expects($this->once())->method('unset');
        $this->sessionUtilMock->expects($this->once())->method('destroySession');
        $this->logManagerMock->expects($this->once())->method('log');

        $this->authManager->logout();
    }
}
