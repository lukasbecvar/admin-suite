<?php

namespace App\Tests\Manager;

use App\Entity\User;
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
    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var ErrorManager */
    private ErrorManager $errorManagerMock;

    /** @var UserManager */
    private UserManager $userManager;

    /** @var UserRepository|MockObject */
    private UserRepository|MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->entityManagerMock->method('getRepository')->willReturn($this->userRepositoryMock);

        $this->userManager = new UserManager($this->logManagerMock, $this->errorManagerMock, $this->entityManagerMock);
    }

    /**
     * Test get user repo
     *
     * @return void
     */
    public function testGetUserRepo(): void
    {
        // mock user repository
        $user = new User();
        $this->userRepositoryMock->method('findOneBy')->willReturn($user);

        // call the method
        $result = $this->userManager->getUserRepo(['username' => 'test']);

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
        $result = $this->userManager->getAllUsersRepository();

        // assert the result
        $this->assertIsArray($result);
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
}
