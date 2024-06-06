<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UserManagerTest
 *
 * Test cases for the UserManager class.
 *
 * @package App\Tests\Manager
 */
class UserManagerTest extends TestCase
{
     /** @var MockObject|LogManager The log manager instance */
    private MockObject|LogManager $logManager;

     /** @var MockObject|SecurityUtil The security util instance */
    private MockObject|SecurityUtil $securityUtil;

     /** @var MockObject|ErrorManager The error manager instance */
    private MockObject|ErrorManager $errorManager;

     /** @var MockObject|VisitorInfoUtil The visitor info util instance */
    private MockObject|VisitorInfoUtil $visitorInfoUtil;

     /** @var MockObject|EntityManagerInterface The entity manager instance */
    private MockObject|EntityManagerInterface $entityManager;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logManager = $this->createMock(LogManager::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * Test case for the getUserRepo method.
     *
     * @return void
     */
    public function testGetUserRepo(): void
    {
        // mock search criteria
        $search = ['email' => 'test@example.com'];

        // mock UserRepository
        $userRepository = $this->createMock(UserRepository::class);

        // mock EntityManager to return UserRepository
        $this->entityManager->expects($this->once())->method('getRepository')->willReturn($userRepository);

        // mock findOneBy method on UserRepository
        $userRepository->expects($this->once())->method('findOneBy')->with($search)->willReturn(new User());

        // create UserManager instance
        $userManager = new UserManager($this->logManager, $this->errorManager, $this->securityUtil, $this->entityManager, $this->visitorInfoUtil);

        // call getUserRepo method
        $result = $userManager->getUserRepo($search);

        // assert that result is an instance of User
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test case for the registerUser method.
     *
     * @return void
     */
    public function testRegisterUser(): void
    {
        // create UserManager instance
        $userManager = new UserManager($this->logManager, $this->errorManager, $this->securityUtil, $this->entityManager, $this->visitorInfoUtil);

        // mock user registration data
        $email = 'test@example.com';
        $password = 'password123';

        // mock UserRepository
        $userRepository = $this->createMock(UserRepository::class);

        // mock EntityManager to return UserRepository
        $this->entityManager->expects($this->atLeastOnce())->method('getRepository')->willReturn($userRepository);

        // mock findOneBy method on UserRepository
        $userRepository->expects($this->atLeastOnce())->method('findOneBy')->willReturn(null);

        // mock SecurityUtil to return a hashed password
        $this->securityUtil->expects($this->once())->method('generateHash')->willReturn('hashed_password');

        // mock VisitorInfoUtil to return an IP address
        $this->visitorInfoUtil->expects($this->once())->method('getIP')->willReturn('127.0.0.1');

        // mock EntityManager to persist and flush user
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // mock LogManager to log registration action
        $this->logManager->expects($this->once())->method('log');

        // call registerUser method
        $userManager->registerUser($email, $password);
    }

    /**
     * Test case for the addAdminRoleToUser method.
     *
     * @return void
     */
    public function testAddAdminRoleToUser(): void
    {
        // create UserManager instance
        $userManager = new UserManager($this->logManager, $this->errorManager, $this->securityUtil, $this->entityManager, $this->visitorInfoUtil);

        // mock user
        $user = new User();
        $user->setUsername('testuser');

        // mock UserRepository
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository = $this->createMock(UserRepository::class));

        // mock findOneBy method on UserRepository
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'testuser'])
            ->willReturn($user);

        // mock LogManager to log action
        $this->entityManager->expects($this->once())
            ->method('flush');

        // call addAdminRoleToUser method
        $userManager->addAdminRoleToUser('testuser');
    }

    /**
     * Test case for the isUserAdmin method.
     *
     * @return void
     */
    public function testIsUserAdmin(): void
    {
        // create UserManager instance
        $userManager = new UserManager($this->logManager, $this->errorManager, $this->securityUtil, $this->entityManager, $this->visitorInfoUtil);

        // mock admin and regular users
        $adminUser = new User();
        $adminUser->setUsername('adminuser');
        $adminUser->setRoles(['ROLE_ADMIN']);

        $regularUser = new User();
        $regularUser->setUsername('regularuser');
        $regularUser->setRoles(['ROLE_USER']);

        // mock UserRepository
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository = $this->createMock(UserRepository::class));

        // mock findOneBy method on UserRepository
        $userRepository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls($adminUser, $regularUser);

        // assert that admin user is admin
        $this->assertTrue($userManager->isUserAdmin('adminuser'));

        // assert that regular user is not admin
        $this->assertFalse($userManager->isUserAdmin('regularuser'));
    }
}
