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

/**
 * Class UserManagerTest
 *
 * Test the user manager
 *
 * @package App\Tests\Manager
 */
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

        // mock user repository
        $this->entityManagerMock->method('getRepository')->willReturn($this->userRepositoryMock);

        // create the user manager instance
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

        // call the method
        $result = $this->userManager->getUserRepository(['username' => 'test']);

        // assert the result
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get all users repos
     *
     * @return void
     */
    public function testGetAllUserRepos(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call the method
        $result = $this->userManager->getAllUsersRepositories();

        // assert the result
        $this->assertIsArray($result);
    }

    /**
     * Test get user by page
     *
     * @return void
     */
    public function testGetUsersByPage(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findBy')->willReturn([$user]);

        // call the method
        $result = $this->userManager->getUsersByPage(1);

        // assert the result
        $this->assertIsArray($result);
    }

    /**
     * Test get user by id
     *
     * @return void
     */
    public function testGetUsersCount(): void
    {
        // call the method
        $result = $this->userManager->getUsersCount();

        // assert the result
        $this->assertSame(0, $result);
    }

    /**
     * Test check if user exist
     *
     * @return void
     */
    public function testCheckIfUserExist(): void
    {
        // mock user repository
        $this->userRepositoryMock->method('findOneBy')->willReturn(new User());

        // call the method
        $result = $this->userManager->checkIfUserExist('test');

        // assert the result
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

        // call the method
        $result = $this->userManager->getUsernameById(1);

        // assert the result
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

        // call the method
        $result = $this->userManager->getUserRoleById(1);

        // assert the result
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

        // call the method
        $result = $this->userManager->isUserAdmin(1);

        // assert the result
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

        // mock the log manager
        $this->logManagerMock->expects($this->once())->method('log');

        // mock the entity manager
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call the method
        $this->userManager->updateUserRole(1, 'admin');

        // assert the result
        $this->assertEquals('ADMIN', $user->getRole());
    }

    /**
     * Test is user database empty
     *
     * @return void
     */
    public function testIsUsersEmpty(): void
    {
        // call get users empty status
        $result = $this->userManager->isUsersEmpty();

        // assert the result
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

        // mock log manager
        $this->logManagerMock->expects($this->once())
            ->method('log')->with('user-manager', 'user: testUser deleted');

        // call method
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

        // configure logManagerMock
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'account-settings',
            'update username (' . $newUsername . ') for user: ' . $user->getUsername()
        );

        // configure entityManagerMock
        $this->entityManagerMock
            ->expects($this->once())->method('flush');

        // call the method under test
        $this->userManager->updateUsername($userId, $newUsername);

        // assert that the username was updated correctly
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

        // configure userRepositoryMock
        $this->userRepositoryMock->expects($this->once())
            ->method('findOneBy')->with(['id' => $userId])->willReturn($user);

        // configure securityUtil mock
        $this->securityUtilMock->expects($this->once())
            ->method('generateHash')->with($newPassword)->willReturn('hashedPassword123');

        // configure logManagerMock
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'account-settings',
            'update password for user: ' . $user->getUsername()
        );

        // configure entityManagerMock
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call the method under test
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

        // configure userRepositoryMock
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $userId])
            ->willReturn($user);

        // configure logManagerMock
        $this->logManagerMock->expects($this->once())
            ->method('log')->with(
                'account-settings',
                'update profile picture for user: ' . $user->getUsername()
            );

        // configure entityManagerMock
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call the method under test
        $this->userManager->updateProfilePicture($userId, $newProfilePicture);
    }
}
