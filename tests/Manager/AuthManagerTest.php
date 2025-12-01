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
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManagerTest
 *
 * Test cases for authentication and authorization manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(AuthManager::class)]
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
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')->willReturn($blockedUsernames);

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
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')->willReturn($blockedUsernames);

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
        $this->appUtilMock->method('loadConfig')->with('blocked-usernames.json')->willReturn($blockedUsernames);

        // expect handle error
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to register new user: username is system',
            Response::HTTP_FORBIDDEN
        );

        // call test method
        $this->authManager->registerUser('admin', 'password');
    }

    /**
     * Test register user when username is already registered
     *
     * @return void
     */
    public function testRegisterUserWhenUsernameIsAlreadyRegistered(): void
    {
        // mock user already exists
        $this->userManagerMock->method('checkIfUserExist')->willReturn(true);

        // mock handleError to throw exception
        $this->errorManagerMock->method('handleError')->willThrowException(
            new Exception('error to register new user: username already exist')
        );

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
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'authenticator',
            'new registration user: newUser',
            LogManager::LEVEL_CRITICAL
        );

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
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->callback(function (User $user) {
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
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->callback(function (User $user) {
            return $user->getUserAgent() === 'Unknown';
        }));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test register user with exception thrown
     *
     * @return void
     */
    public function testRegisterUserExceptionThrown(): void
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
        $this->entityManagerMock->expects($this->once())->method('persist')->willThrowException(
            new Exception('Database error')
        );
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to register new user: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call test method
        $this->authManager->registerUser('newUser', 'password');
    }

    /**
     * Test check if user is logged in when session not exist
     *
     * @return void
     */
    public function testIsUserLogedinWhenSessionNotExist(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(false);

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user is logged in when session exist but token is not valid
     *
     * @return void
     */
    public function testIsUserLogedinWhenSessionExistButTokenIsNotValid(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('invalidToken');

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user is logged in when session exist and token is valid
     *
     * @return void
     */
    public function testIsUserLogedinWhenSessionExistAndTokenIsValid(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getToken')->willReturn('validToken');
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if user can login when username is empty
     *
     * @return void
     */
    public function testCheckifUserCanLoginWhenUsernameIsEmpty(): void
    {
        // call test method
        $result = $this->authManager->canLogin('', 'testpassword');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user can login when password is empty
     *
     * @return void
     */
    public function testCheckifUserCanLoginWhenPasswordIsEmpty(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // call test method
        $result = $this->authManager->canLogin('testuser', '');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user can login when user not exist
     *
     * @return void
     */
    public function testCheckifUserCanLoginWhenUserNotExist(): void
    {
        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'authenticator',
            'invalid login user: testuser:testpassword',
            LogManager::LEVEL_CRITICAL
        );

        // call test method
        $result = $this->authManager->canLogin('testuser', 'testpassword');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user can login when user exist and password is incorrect
     *
     * @return void
     */
    public function testCheckifUserCanLoginWhenUserExistAndPasswordIsIncorrect(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // mock password verification
        $this->securityUtilMock->method('verifyPassword')->willReturn(false);

        // call test method
        $result = $this->authManager->canLogin('testuser', 'wrongpassword');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if user can login when user exist and password is correct
     *
     * @return void
     */
    public function testCheckifUserCanLoginWhenUserExistAndPasswordIsCorrect(): void
    {
        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn('hashedPassword');
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);

        // mock password verification
        $this->securityUtilMock->method('verifyPassword')->willReturn(true);

        // call test method
        $result = $this->authManager->canLogin('testuser', 'correctpassword');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test handle user login
     *
     * @return void
     */
    public function testHandleUserLogin(): void
    {
        // mock user object
        $user = $this->createMock(User::class);
        $user->method('getToken')->willReturn('test_token');
        $user->method('getId')->willReturn(123);

        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn($user);
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // expect session id regeneration
        $this->sessionUtilMock->expects($this->once())->method('regenerateSession');

        // expect session set
        $this->sessionUtilMock->expects($this->exactly(2))->method('setSession');

        // expect cookie set
        $this->cookieUtilMock->expects($this->once())->method('set')->with(
            'user-token',
            'test_token',
            $this->anything()
        );

        // expect entity manager call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log');

        // call test method
        $this->authManager->login('test_user', true);
    }

    /**
     * Test update user data on login
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
     * Test get logged user repository when user not logged in
     *
     * @return void
     */
    public function testGetLoggedUserRepositoryWhenUserisNotLoggedIn(): void
    {
        // mock user logged in status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // call test method
        $result = $this->authManager->getLoggedUserRepository();

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get logged user repository when user not found
     *
     * @return void
     */
    public function testGetLoggedUserRepositoryWhenUserNotFound(): void
    {
        // mock user logged status & session get
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // call test method
        $result = $this->authManager->getLoggedUserRepository();

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test check if logged user is admin when user not logged in
     *
     * @return void
     */
    public function testCheckIfLoggedUserIsAdminWhenUserNotLoggedIn(): void
    {
        // mock user logged in status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // call test method
        $result = $this->authManager->isLoggedInUserAdmin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if logged user is admin when user not found
     *
     * @return void
     */
    public function testCheckIfLoggedUserIsAdminWhenUserNotFound(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn(null);

        // call test method
        $result = $this->authManager->isLoggedInUserAdmin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if logged user is admin when user invalid token
     *
     * @return void
     */
    public function testCheckIfLoggedUserIsAdminWhenUserInvalidToken(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($user);

        // mock user admin status
        $this->userManagerMock->method('isUserAdmin')->willReturn(false);

        // call test method
        $result = $this->authManager->isLoggedInUserAdmin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if logged user is admin when user not admin
     *
     * @return void
     */
    public function testCheckIfLoggedUserIsAdminWhenUserNotAdmin(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user repository
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($user);

        // mock user admin status
        $this->userManagerMock->method('isUserAdmin')->willReturn(false);

        // call test method
        $result = $this->authManager->isLoggedInUserAdmin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check is user logged in when token is not string
     *
     * @return void
     */
    public function testCheckIsUserLogedinWhenTokenIsNotString(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn(123);

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check is user logged in when token exists but user not found
     *
     * @return void
     */
    public function testCheckIsUserLogedinWhenTokenExistsButUserNotFound(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check is user logged in when token exists and user found
     *
     * @return void
     */
    public function testCheckIsUserLogedinWhenTokenExistsAndUserFound(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = $this->createMock(User::class);
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check is user logged in when token type is invalid
     *
     * @return void
     */
    public function testCheckIsUserLogedinWhenTokenTypeIsInvalid(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn(123);

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call test method
        $result = $this->authManager->isUserLogedin();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get logged user id when user is not logged in
     *
     * @return void
     */
    public function testGetLoggedUserIdWhenUserisNotLoggedIn(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(false);

        // call test method
        $result = $this->authManager->getLoggedUserId();

        // assert result
        $this->assertEquals(0, $result);
    }

    /**
     * Test get logged user id when user not found
     *
     * @return void
     */
    public function testGetLoggedUserIdWhenUserNotFound(): void
    {
        // mock user logged status
        $this->authManagerMock->method('isUserLogedin')->willReturn(true);

        // mock user token
        $this->authManagerMock->method('getLoggedUserToken')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // call test method
        $result = $this->authManager->getLoggedUserId();

        // assert result
        $this->assertEquals(0, $result);
    }

    /**
     * Test get logged user id when user found and logged in
     *
     * @return void
     */
    public function testGetLoggedUserIdWhenUserFoundAndLoggedIn(): void
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

        // call test method
        $result = $this->authManager->getLoggedUserId();

        // assert result
        $this->assertEquals(1, $result);
    }

    /**
     * Test get logged user id when user not logged in
     *
     * @return void
     */
    public function testGetLoggedUserTokenWhenUserNotLoggedIn(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(false);

        // call test method
        $result = $this->authManager->getLoggedUserToken();

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get logged user token when user not found
     *
     * @return void
     */
    public function testGetLoggedUserTokenWhenUserNotFound(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $this->userManagerMock->method('getUserByToken')->willReturn(null);

        // call test method
        $result = $this->authManager->getLoggedUserToken();

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get logged user token when user found and logged in
     *
     * @return void
     */
    public function testGetLoggedUserTokenWhenUserFoundAndLoggedIn(): void
    {
        // mock session check
        $this->sessionUtilMock->method('checkSession')->willReturn(true);

        // mock session value get
        $this->sessionUtilMock->method('getSessionValue')->willReturn('validToken');

        // mock user repository
        $user = new User();
        $user->setToken('validToken');
        $this->userManagerMock->method('getUserByToken')->willReturn($user);

        // call test method
        $result = $this->authManager->getLoggedUserToken();

        // assert result
        $this->assertEquals('validToken', $result);
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

        // call test method
        $result = $this->authManager->getLoggedUsername();

        // assert result
        $this->assertEquals('testuser', $result);
    }

    /**
     * Test user logout process
     *
     * @return void
     */
    public function testUserLogoutProcess(): void
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
     * Test reset user password when user not found
     *
     * @return void
     */
    public function testResetUserPasswordWhenUserNotFound(): void
    {
        // mock user repository
        $this->userManagerMock->method('getUserByUsername')->willReturn(null);

        // call test method
        $newPassword = $this->authManager->resetUserPassword('nonexistentUser');

        // assert result
        $this->assertNull($newPassword);
    }

    /**
     * Test reset user password with success result
     *
     * @return void
     */
    public function testResetUserPasswordWithSuccessResult(): void
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
     * Test regenerate all users tokens
     *
     * @return void
     */
    public function testRegenerateAllUsersTokens(): void
    {
        // mock user repository
        $user1 = $this->createMock(User::class);
        $user1->expects($this->once())->method('setToken')->with($this->callback(function (mixed $token) {
            return is_string($token);
        }));
        $user2 = $this->createMock(User::class);
        $user2->expects($this->once())->method('setToken')->with($this->callback(function (mixed $token) {
            return is_string($token);
        }));
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
        // call test method
        $token = $this->authManager->generateUserToken();

        // assert result
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test authenticate with api key returns false and logs when token invalid
     *
     * @return void
     */
    public function testAuthenticateWithApiKeyReturnsFalseWhenTokenInvalid(): void
    {
        // mock invalid token
        $token = 'invalid-token';
        $this->userManagerMock->expects($this->once())->method('getUserByToken')->with($token)->willReturn(null);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'api-authentication',
            'invalid api key authentication with token: ' . $token,
            LogManager::LEVEL_CRITICAL
        );

        // expect set session call (never)
        $this->sessionUtilMock->expects($this->never())->method('setSession');

        // call tested method
        $result = $this->authManager->authenticateWithApiKey($token);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test authenticate with api key hydrates session when token valid and API access enabled
     *
     * @return void
     */
    public function testAuthenticateWithApiKeyHydratesSessionWhenApiAccessAllowed(): void
    {
        // mock valid token
        $token = 'valid-token';
        $user = new User();
        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, 42);
        $user->setAllowApiAccess(true);
        $this->userManagerMock->expects($this->once())->method('getUserByToken')->with($token)->willReturn($user);

        // mock request uri & method
        $this->visitorInfoUtilMock->method('getRequestUri')->willReturn('/test-url');
        $this->visitorInfoUtilMock->method('getRequestMethod')->willReturn('test-method');

        // expect set session calls
        $expectedCalls = [
            ['user-token', $token],
            ['user-identifier', '42']
        ];
        $this->sessionUtilMock->expects($this->exactly(2))->method('setSession')
            ->willReturnCallback(function (string $name, string $value) use (&$expectedCalls): void {
                $expected = array_shift($expectedCalls);
                if ($expected === null) {
                    $this->fail('Unexpected setSession call');
                }
                [$expectedName, $expectedValue] = $expected;
                $this->assertEquals($expectedName, $name);
                $this->assertEquals($expectedValue, $value);
            });

        // expect log manager call (never)
        $this->logManagerMock->expects($this->never())->method('log');

        // expect log api access call
        $this->logManagerMock->expects($this->once())->method('logApiAccess')->with(
            $this->equalTo('/test-url'),
            $this->equalTo('test-method'),
            $this->equalTo(42)
        );

        // call tested method
        $result = $this->authManager->authenticateWithApiKey($token);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test authenticate with api key denied when API access disabled
     *
     * @return void
     */
    public function testAuthenticateWithApiKeyDeniedWhenApiAccessDisabled(): void
    {
        // mock user
        $token = 'valid-token';
        $user = new User();
        $user->setUsername('api-user');
        $user->setAllowApiAccess(false);
        $this->userManagerMock->expects($this->once())->method('getUserByToken')->with($token)->willReturn($user);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'api-authentication',
            'api key authentication: api-user is not allowed to use api',
            LogManager::LEVEL_CRITICAL
        );

        // expect session not set
        $this->sessionUtilMock->expects($this->never())->method('setSession');

        // call tested method
        $result = $this->authManager->authenticateWithApiKey($token);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test regenerate user token when user does not exist
     *
     * @return void
     */
    public function testRegenerateUserTokenWhenUserDoesNotExist(): void
    {
        // mock getUserRepository to return null (user not found)
        $this->userManagerMock->method('getUserRepository')->willReturn(null);

        // call tested method
        $result = $this->authManager->regenerateSpecificUserToken(999);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test cache online user
     *
     * @return void
     */
    public function testCacheOnlineUser(): void
    {
        $onlineListCacheItem = $this->createMock(CacheItemInterface::class);
        $onlineListCacheItem->method('get')->willReturn([]);

        // mock cache
        $this->cacheUtilMock->expects($this->once())->method('getValue')->with($this->equalTo('online_users_list'))->willReturn($onlineListCacheItem);

        $setInvocation = 0;
        $this->cacheUtilMock->expects($this->exactly(2))->method('setValue')->willReturnCallback(function (string $key, mixed $value, int $ttl) use (&$setInvocation) {
            if ($setInvocation === 0) {
                $this->assertSame('online_user_123', $key);
                $this->assertSame('online', $value);
                $this->assertSame(300, $ttl);
            } else {
                $this->assertSame('online_users_list', $key);
                $this->assertEquals([123], $value);
                $this->assertSame(300, $ttl);
            }
            $setInvocation++;
        });

        // call test method
        $this->authManager->cacheOnlineUser(123);
    }

    /**
     * Test get online users list
     *
     * @return void
     */
    public function testGetOnlineUsersList(): void
    {
        $onlineListCacheItem = $this->createMock(CacheItemInterface::class);
        $onlineListCacheItem->method('get')->willReturn([1]);
        $onlineStatusCacheItem = $this->createMock(CacheItemInterface::class);
        $onlineStatusCacheItem->method('get')->willReturn('online');

        $getInvocation = 0;
        $this->cacheUtilMock->expects($this->exactly(2))->method('getValue')->willReturnCallback(function (string $key) use (&$getInvocation, $onlineListCacheItem, $onlineStatusCacheItem) {
            if ($getInvocation === 0) {
                $this->assertSame('online_users_list', $key);
                $getInvocation++;
                return $onlineListCacheItem;
            }
            $this->assertSame('online_user_1', $key);
            $getInvocation++;

            return $onlineStatusCacheItem;
        });

        $user = $this->createMock(User::class);
        $this->userManagerMock->expects($this->once())->method('getUsersByIds')->with([1])->willReturn([$user]);

        // call tested method
        $result = $this->authManager->getOnlineUsersList();

        // assert result
        $this->assertSame([$user], $result);
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
