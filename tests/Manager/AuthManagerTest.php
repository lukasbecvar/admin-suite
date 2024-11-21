<?php

namespace App\Tests\Manager;

use Exception;
use ReflectionClass;
use App\Entity\User;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\EmailManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManagerTest
 *
 * Test cases for the authentication manager
 *
 * @package App\Tests\Manager
 */
class AuthManagerTest extends TestCase
{
    private AuthManager $authManager;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private LogManager & MockObject $logManagerMock;
    private CookieUtil & MockObject $cookieUtilMock;
    private AuthManager & MockObject $authManagerMock;
    private SessionUtil & MockObject $sessionUtilMock;
    private UserManager & MockObject $userManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private EmailManager & MockObject $emailManagerMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->emailManagerMock = $this->createMock(EmailManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create auth manager instance
        $this->authManager = new AuthManager(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->logManagerMock,
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->userManagerMock,
            $this->emailManagerMock,
            $this->errorManagerMock,
            $this->securityUtilMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test check if username is blocked with blocked username
     *
     * @return void
     */
    public function testIsUsernameBlockedReturnsTrueWhenUsernameIsBlocked(): void
    {
        $blockedUsernames = ['admin', 'system', 'root'];

        // mock blocked usernames config
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')
            ->willReturn($blockedUsernames);

        // check if username is blocked
        $result = $this->authManager->isUsernameBlocked('admin');

        // assert username is blocked
        $this->assertTrue($result);
    }

    /**
     * Test check if username is blocked with unblocked username
     *
     * @return void
     */
    public function testIsUsernameBlockedReturnsFalseWhenUsernameIsNotBlocked(): void
    {
        $blockedUsernames = ['admin', 'system', 'root'];

        // mock blocked usernames config
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')
            ->willReturn($blockedUsernames);

        // check if username is blocked
        $result = $this->authManager->isUsernameBlocked('user');

        // assert username is not blocked
        $this->assertFalse($result);
    }

    /**
     * Test register user with blocked username
     *
     * @return void
     */
    public function testRegisterUserBlockedUsername(): void
    {
        $blockedUsernames = ['admin', 'system', 'root'];

        // mock blocked usernames config
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')
            ->willReturn($blockedUsernames);

        // expect handle error
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to register new user: username is system', Response::HTTP_FORBIDDEN);

        // call test method
        $this->authManager->registerUser('admin', 'password');
    }

    /**
     * Test register user with already existing username
     *
     * @return void
     */
    public function testRegisterUserAlreadyExists(): void
    {
        // mock user already exists
        $this->userManagerMock->method('checkIfUserExist')->willReturn(true);

        // mock handleError to throw exception
        $this->errorManagerMock
            ->method('handleError')
            ->willThrowException(new Exception('error to register new user: username already exist'));

        // expect entity manager not to be called
        $this->entityManagerMock->expects($this->never())->method('persist');

        // expect exception to be thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('error to register new user: username already exist');

        // call test method
        $this->authManager->registerUser('existingUser', 'password');
    }


    /**
     * Test register user with successful registration
     *
     * @return void
     */
    public function testRegisterUserSuccessful(): void
    {
        // mock user manager
        $this->userManagerMock->method('checkIfUserExist')->willReturn(false);
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Mozilla/5.0');

        // mock security util
        $this->securityUtilMock->method('generateHash')->willReturn('hashedPassword');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(User::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')
            ->with('authenticator', 'new registration user: newUser', LogManager::LEVEL_CRITICAL);

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test register user with null IP address
     *
     * @return void
     */
    public function testRegisterUserNullIpAddress(): void
    {
        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn(null);
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Mozilla/5.0');

        // mock security util
        $this->securityUtilMock->method('generateHash')->willReturn('hashedPassword');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->callback(function ($user) {
            return $user->getIpAddress() === 'Unknown';
        }));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test register user with null user agent
     *
     * @return void
     */
    public function testRegisterUserNullUserAgent(): void
    {
        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn(null);

        // mock security util
        $this->securityUtilMock->method('generateHash')->willReturn('hashedPassword');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->callback(function ($user) {
            return $user->getUserAgent() === 'Unknown';
        }));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test register user with exception during save
     *
     * @return void
     */
    public function testRegisterUserExceptionDuringSave(): void
    {
        // mock user manager
        $this->userManagerMock->method('checkIfUserExist')->willReturn(false);
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Mozilla/5.0');

        // mock security util
        $this->securityUtilMock->method('generateHash')->willReturn('hashedPassword');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('persist')
            ->willThrowException(new Exception('Database error'));
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to register new user: Database error', Response::HTTP_INTERNAL_SERVER_ERROR);

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test get logged user repository with user not logged in
     *
     * @return void
     */
    public function testGetLoggedUserRepositoryUserNotLoggedIn(): void
    {
        // mock user logged in status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // assert result
        $this->assertNull($this->authManager->getLoggedUserRepository());
    }

    /**
     * Test get logged user repository with user not found
     *
     * @return void
     */
    public function testGetLoggedUserRepositoryUserNotFound(): void
    {
        // mock user logged status & session get
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // assert result
        $this->assertNull($this->authManager->getLoggedUserRepository());
    }

    /**
     * Test is user logged in with user not logged in
     *
     * @return void
     */
    public function testIsLoggedInUserAdminUserNotLoggedIn(): void
    {
        // mock user logged in status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // assert result
        $this->assertFalse($this->authManager->isLoggedInUserAdmin());
    }

    /**
     * Test is user logged in with user not admin
     *
     * @return void
     */
    public function testIsLoggedInUserAdminUserNotAdmin(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($user);

        // mock user admin status
        $this->userManagerMock->method('isUserAdmin')->willReturn(false);

        // assert result
        $this->assertFalse($this->authManager->isLoggedInUserAdmin());
    }

    /**
     * Test is user logged in with user not found
     *
     * @return void
     */
    public function testIsLoggedInUserAdminUserNotFound(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn(null);

        // assert result
        $this->assertFalse($this->authManager->isLoggedInUserAdmin());
    }

    /**
     * Test is user logged in with user invalid token
     *
     * @return void
     */
    public function testIsLoggedInUserAdminUserInvalidToken(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($user);

        // mock user admin status
        $this->userManagerMock->method('isUserAdmin')->willReturn(false);

        // assert result
        $this->assertFalse($this->authManager->isLoggedInUserAdmin());
    }

    /**
     * Test is user logged in with user admin
     *
     * @return void
     */
    public function testIsUserLogedinNoSession(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(false);

        // assert result
        $this->assertFalse($this->authManager->isUserLogedin());
    }

    /**
     * Test is user logged in with token not string
     *
     * @return void
     */
    public function testIsUserLogedinTokenNotString(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn(123);

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // assert result
        $this->assertFalse($this->authManager->isUserLogedin());
    }

    /**
     * Test is user logged in with token exists but no user
     *
     * @return void
     */
    public function testIsUserLogedinTokenExistsButNoUser(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // assert result
        $this->assertFalse($this->authManager->isUserLogedin());
    }

    /**
     * Test is user logged in with token exists and user found
     *
     * @return void
     */
    public function testIsUserLogedinTokenExistsAndUserFound(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = $this->createMock(User::class);
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // assert result
        $this->assertTrue($this->authManager->isUserLogedin());
    }

    /**
     * Test is user logged in with invalid token type
     *
     * @return void
     */
    public function testIsUserLogedinInvalidTokenType(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn(123);

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // assert result
        $this->assertFalse($this->authManager->isUserLogedin());
    }

    /**
     * Test can login with user not exist
     *
     * @return void
     */
    public function testCanLoginUserNotExist(): void
    {
        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'authenticator',
            'invalid login user: testuser:testpassword',
            LogManager::LEVEL_CRITICAL
        );

        // assert result
        $this->assertFalse($this->authManager->canLogin('testuser', 'testpassword'));
    }

    /**
     * Test can login with correct password
     *
     * @return void
     */
    public function testCanLoginCorrectPassword(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // mock password verification
        $this->securityUtilMock->method('verifyPassword')->willReturn(true);

        // assert result
        $this->assertTrue($this->authManager->canLogin('testuser', 'correctpassword'));
    }

    /**
     * Test can login with incorrect password
     *
     * @return void
     */
    public function testCanLoginIncorrectPassword(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // mock password verification
        $this->securityUtilMock->method('verifyPassword')->willReturn(false);

        // assert result
        $this->assertFalse($this->authManager->canLogin('testuser', 'wrongpassword'));
    }

    /**
     * Test can login with empty username
     *
     * @return void
     */
    public function testCanLoginEmptyUsername(): void
    {
        // assert result
        $this->assertFalse($this->authManager->canLogin('', 'testpassword'));
    }

    /**
     * Test can login with empty password
     *
     * @return void
     */
    public function testCanLoginEmptyPassword(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // assert result
        $this->assertFalse($this->authManager->canLogin('testuser', ''));
    }

    /**
     * Test login process
     *
     * @return void
     */
    public function testLoginProcess(): void
    {
        // mock user object
        $user = $this->createMock(User::class);
        $user->method('getToken')->willReturn('test_token');
        $user->method('getId')->willReturn(123);

        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // expect session set
        $this->sessionUtilMock->expects($this->exactly(2))->method('setSession');

        // expect cookie set
        $this->cookieUtilMock->expects($this->once())->method('set')
            ->with('user-token', 'test_token', $this->anything());

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log');

        // call test method
        $this->authManager->login('test_user', true);
    }

    /**
     * Test login process with null ip address
     *
     * @return void
     */
    public function testUpdateDataOnLoginSuccess(): void
    {
        // mock user object
        $user = $this->createMock(User::class);
        $user->method('setLastLoginTime')->willReturnSelf();
        $user->method('setIpAddress')->willReturnSelf();
        $user->method('setUserAgent')->willReturnSelf();

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('192.168.1.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Mozilla/5.0');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect user setter calls
        $user->expects($this->once())->method('setLastLoginTime');
        $user->expects($this->once())->method('setIpAddress');
        $user->expects($this->once())->method('setUserAgent');

        // call test method
        $this->authManager->updateDataOnLogin('valid_token');
    }

    /**
     * Test update data on login with database error
     *
     * @return void
     */
    public function testUpdateDataOnLoginDatabaseError(): void
    {
        // mock user object
        $user = $this->createMock(User::class);
        $user->method('setLastLoginTime')->willReturnSelf();
        $user->method('setIpAddress')->willReturnSelf();
        $user->method('setUserAgent')->willReturnSelf();

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('192.168.1.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Mozilla/5.0');

        // mock flush exception
        $this->entityManagerMock->method('flush')->will($this->throwException(new Exception('Database error')));

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update user data: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call test method
        $this->authManager->updateDataOnLogin('valid_token');
    }

    /**
     * Test update data on login with invalid token
     *
     * @return void
     */
    public function testGetLoggedUserIdNotLoggedIn(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // assert result
        $this->assertEquals(0, $this->authManager->getLoggedUserId());
    }

    /**
     * Test get logged user id with user not found
     *
     * @return void
     */
    public function testGetLoggedUserIdUserNotFound(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user token
        $this->authManagerMock->method('getLoggedUserToken')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // assert result
        $this->assertEquals(0, $this->authManager->getLoggedUserId());
    }

    /**
     * Test get logged user id with success
     *
     * @return void
     */
    public function testGetLoggedUserIdSuccess(): void
    {
        // mock user object
        $user = new User();
        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 1);

        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // assert result
        $result = $this->authManager->getLoggedUserId();

        // assert result
        $this->assertEquals(1, $result);
    }

    /**
     * Test get logged user id with invalid token type
     *
     * @return void
     */
    public function testGetLoggedUserTokenNoSession(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(false);

        // assert result
        $this->assertNull($this->authManager->getLoggedUserToken());
    }

    /**
     * Test get logged user token with user not found
     *
     * @return void
     */
    public function testGetLoggedUserTokenUserNotFound(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // assert result
        $this->assertNull($this->authManager->getLoggedUserToken());
    }

    /**
     * Test get logged user token with success
     *
     * @return void
     */
    public function testGetLoggedUserTokenSuccess(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = new User();
        $user->setToken('validToken');
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // assert result
        $this->assertEquals('validToken', $this->authManager->getLoggedUserToken());
    }

    /**
     * Test get logged username
     *
     * @return void
     */
    public function testGetLoggedUsername(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getUsername')->willReturn('testuser');
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // assert result
        $this->assertEquals('testuser', $this->authManager->getLoggedUsername());
    }

    /**
     * Test logout process
     *
     * @return void
     */
    public function testLogoutProcess(): void
    {
        // mock session util
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('test_token');
        $this->sessionUtilMock->expects($this->once())->method('destroySession');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(new User());

        // expect cookie unset
        $this->cookieUtilMock->expects($this->once())->method('unset');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log');

        // call test method
        $this->authManager->logout();
    }

    /**
     * Test reset user password with user not found
     *
     * @return void
     */
    public function testResetUserPasswordUserNotFound(): void
    {
        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // call test method
        $newPassword = $this->authManager->resetUserPassword('nonexistentUser');

        // assert result
        $this->assertNull($newPassword);
    }

    /**
     * Test reset user password with success
     *
     * @return void
     */
    public function testResetUserPasswordSuccess(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('setPassword');
        $user->method('setToken');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // mock security util
        $this->securityUtilMock->method('generateHash')->willReturn('hashedPassword');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'authenticator',
            'user: testuser password reset is success',
            LogManager::LEVEL_CRITICAL
        );

        // call test method
        $newPassword = $this->authManager->resetUserPassword('testuser');

        // assert result
        $this->assertNotNull($newPassword);
    }

    /**
     * Test regenerate users tokens
     *
     * @return void
     */
    public function testRegenerateUsersTokens(): void
    {
        // mock user repository
        $user1 = $this->createMock(User::class);
        $user1->expects($this->once())->method('setToken')->with($this->isType('string'));
        $user2 = $this->createMock(User::class);
        $user2->expects($this->once())->method('setToken')->with($this->isType('string'));
        $this->userManagerMock->method('getAllUsersRepositories')->willReturn([$user1, $user2]);

        // mock auth manager
        $this->authManagerMock->method('generateUserToken')->willReturn('newToken');

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'authenticator',
            'regenerate all users tokens',
            LogManager::LEVEL_WARNING
        );

        // call test method
        $state = $this->authManager->regenerateUsersTokens();

        // assert result
        $this->assertTrue($state['status']);
        $this->assertNull($state['message']);
    }

    /**
     * Test generate user token
     *
     * @return void
     */
    public function testGenerateUserToken(): void
    {
        // generate user token
        $token = $this->authManager->generateUserToken();

        // assert result
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test cache online user
     *
     * @return void
     */
    public function testCacheOnlineUser(): void
    {
        // expect cache set
        $this->cacheUtilMock->expects($this->once())->method('setValue')->with(
            $this->equalTo('online_user_123'),
            $this->equalTo('online'),
            $this->equalTo(300)
        );

        // call test method
        $this->authManager->cacheOnlineUser(123);
    }

    /**
     * Test get user status with online cache
     *
     * @return void
     */
    public function testGetUserStatusWithOnlineCache(): void
    {
        $userId = 1;
        $userCacheKey = 'online_user_' . $userId;

        // mock cache item
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->expects($this->once())->method('get')->willReturn('online');

        // expect cache get
        $this->cacheUtilMock->expects($this->once())->method('getValue')->with($userCacheKey)
            ->willReturn($cacheItemMock);

        // call test method
        $status = $this->authManager->getUserStatus($userId);

        // assert result
        $this->assertEquals('online', $status);
    }

    /**
     * Test get user status with offline cache
     *
     * @return void
     */
    public function testGetUserStatusWithOfflineCache(): void
    {
        $userId = 1;
        $userCacheKey = 'online_user_' . $userId;

        // mock cache item
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->expects($this->once())->method('get')->willReturn('offline');

        // expect cache get
        $this->cacheUtilMock->expects($this->once())->method('getValue')->with($userCacheKey)
            ->willReturn($cacheItemMock);

        // call test method
        $status = $this->authManager->getUserStatus($userId);

        // assert result
        $this->assertEquals('offline', $status);
    }
}
