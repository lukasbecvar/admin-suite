<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\AppUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class UserManagerTest
 *
 * Test cases for user manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(UserManager::class)]
class UserManagerTest extends TestCase
{
    private UserManager $userManager;
    private ErrorManager $errorManagerMock;
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private UserRepository & MockObject $userRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create user manager instance
        $this->userManager = new UserManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->securityUtilMock,
            $this->errorManagerMock,
            $this->userRepositoryMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test get user repository
     *
     * @return void
     */
    public function testGetUserRepository(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call tested method
        $result = $this->userManager->getUserRepository(['username' => 'test']);

        // assert result
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get all users repositories
     *
     * @return void
     */
    public function testGetAllUsersRepositories(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call tested method
        $result = $this->userManager->getAllUsersRepositories();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get user by username
     *
     * @return void
     */
    public function testGetUserByUsername(): void
    {
        // mock find user
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['username' => 'test'])
            ->willReturn($this->createMock(User::class));

        // call tested method
        $result = $this->userManager->getUserByUsername('test');

        // assert result
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get user by id
     *
     * @return void
     */
    public function testGetUserById(): void
    {
        // expect find method call
        $this->userRepositoryMock->expects($this->once())->method('find')->with(1)
            ->willReturn($this->createMock(User::class));

        // call tested method
        $result = $this->userManager->getUserById(1);

        // assert result
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get user by token
     *
     * @return void
     */
    public function testGetUserByToken(): void
    {
        // expect find method call
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['token' => 'test'])
            ->willReturn($this->createMock(User::class));

        // call tested method
        $result = $this->userManager->getUserByToken('test');

        // assert result
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get users count
     *
     * @return void
     */
    public function testGetUsersCount(): void
    {
        // call tested method
        $result = $this->userManager->getUsersCount();

        // assert result
        $this->assertSame(0, $result);
    }

    /**
     * Test get user by page
     *
     * @return void
     */
    public function testGetUsersByPage(): void
    {
        // expect findBy method call
        $this->userRepositoryMock->expects($this->once())->method('findBy');

        // call tested method
        $result = $this->userManager->getUsersByPage(1);

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test check if user exist
     *
     * @return void
     */
    public function testCheckIfUserExist(): void
    {
        // expect findOneBy method call
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['username' => 'test'])
            ->willReturn($this->createMock(User::class));

        // call tested method
        $result = $this->userManager->checkIfUserExist('test');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if user exist by id
     *
     * @return void
     */
    public function testCheckIfUserExistById(): void
    {
        // expect findOneBy method call
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['id' => 1])
            ->willReturn($this->createMock(User::class));

        // call tested method
        $result = $this->userManager->checkIfUserExistById(1);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test get username by id
     *
     * @return void
     */
    public function testGetUsernameById(): void
    {
        // mock user repository
        $user = new User();
        $user->setUsername('test');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call tested method
        $result = $this->userManager->getUsernameById(1);

        // assert result
        $this->assertEquals('test', $result);
    }

    /**
     * Test get user role by id
     *
     * @return void
     */
    public function testGetUserRoleById(): void
    {
        // mock user repository
        $user = new User();
        $user->setRole('ROLE_USER');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call tested method
        $result = $this->userManager->getUserRoleById(1);

        // assert result
        $this->assertEquals('ROLE_USER', $result);
    }

    /**
     * Test is user admin
     *
     * @return void
     */
    public function testIsUserAdmin(): void
    {
        // mock user repository
        $user = new User();
        $user->setRole('ADMIN');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call tested method
        $result = $this->userManager->isUserAdmin(1);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test update user role
     *
     * @return void
     */
    public function testUpdateUserRole(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // expect call log manager
        $this->logManagerMock->expects($this->once())->method('log');

        // expect flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->userManager->updateUserRole(1, 'admin');

        // assert result
        $this->assertEquals('ADMIN', $user->getRole());
    }

    /**
     * Test check if users database is empty
     *
     * @return void
     */
    public function testCheckIfUsersDatabaseIsEmpty(): void
    {
        // call tested method
        $result = $this->userManager->isUsersEmpty();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test delete user
     *
     * @return void
     */
    public function testDeleteUser(): void
    {
        // mock user entity
        $user = new User();
        $user->setUsername('testUser');
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('remove')->with($user);
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect call log manager
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'user-manager',
            'user: testUser deleted',
            LogManager::LEVEL_WARNING
        );

        // call tested method
        $this->userManager->deleteUser(1);
    }

    /**
     * Test update username
     *
     * @return void
     */
    public function testUpdateUsername(): void
    {
        // prepare test data
        $userId = 1;
        $newUsername = 'newUsername';

        // mock user instance
        $user = new User();
        $user->setUsername('oldUsername');

        // configure userRepositoryMock
        $this->userRepositoryMock->expects($this->once())
            ->method('findOneBy')->with(['id' => $userId])->willReturn($user);

        // expect call log manager
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'account-settings',
            'update username (' . $newUsername . ') for user: ' . $user->getUsername()
        );

        // expect flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->userManager->updateUsername($userId, $newUsername);

        // assert result
        $this->assertEquals($newUsername, $user->getUsername());
    }

    /**
     * Test update password
     *
     * @return void
     */
    public function testUpdatePassword(): void
    {
        // prepare test data
        $userId = 1;
        $newPassword = 'newPassword123';

        // mock user instance
        $user = new User();
        $user->setUsername('testUser');

        // mock find user
        $this->userRepositoryMock->expects($this->once())
            ->method('findOneBy')->with(['id' => $userId])->willReturn($user);

        // mock hash generator
        $this->securityUtilMock->expects($this->once())
            ->method('generateHash')->with($newPassword)->willReturn('hashedPassword123');

        // expect call log manager
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'account-settings',
            'update password for user: ' . $user->getUsername()
        );

        // expect flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->userManager->updatePassword($userId, $newPassword);
    }

    /**
     * Test update profile picture
     *
     * @return void
     */
    public function testUpdateProfilePicture(): void
    {
        // prepare test data
        $userId = 1;
        $newProfilePicture = 'base64-encoded-profile-picture-data';

        // mock user instance
        $user = new User();
        $user->setUsername('testUser');

        // mock user
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')
            ->with(['id' => $userId])->willReturn($user);

        // expect call log manager
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'account-settings',
            'update profile picture for user: ' . $user->getUsername()
        );

        // expect flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->userManager->updateProfilePicture($userId, $newProfilePicture);
    }
}
