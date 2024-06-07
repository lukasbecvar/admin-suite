<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UserManagerTest
 *
 * Contains unit tests for the UserManager class.
 *
 * @package App\Tests\Manager
 */
class UserManagerTest extends TestCase
{
    /** @var UserManager */
    private UserManager $userManager;

    /** @var MockObject|LogManager */
    private LogManager|MockObject $logManager;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManager;

    /** @var MockObject|SecurityUtil */
    private SecurityUtil|MockObject $securityUtil;

    /** @var MockObject|UserRepository */
    private UserRepository|MockObject $userRepository;

    /** @var MockObject|VisitorInfoUtil */
    private VisitorInfoUtil|MockObject $visitorInfoUtil;

    /** @var MockObject|EntityManagerInterface */
    private EntityManagerInterface|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->logManager = $this->createMock(LogManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // create a mock repository that extends EntityRepository
        $this->userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        // set the getRepository method to return the mock repository
        $this->entityManager->method('getRepository')->willReturn($this->userRepository);

        // create a new instance of the UserManager class
        $this->userManager = new UserManager(
            $this->logManager,
            $this->errorManager,
            $this->securityUtil,
            $this->entityManager,
            $this->visitorInfoUtil
        );
    }

    /**
     * Test successful user registration.
     *
     * @return void
     */
    public function testRegisterUserSuccessfully(): void
    {
        // create a new user object
        $this->userRepository->method('findOneBy')->willReturn(null);

        // mock the log method
        $this->logManager->expects($this->once())
            ->method('log')
            ->with('authenticator', 'new registration user: testuser');

        // mock the persist method
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        // mock the flush method
        $this->entityManager->expects($this->once())
            ->method('flush');

        // call the registerUser method
        $this->userManager->registerUser('testuser', 'password');
    }

    /**
     * Test the checkIfUserExist method with an existing user.
     *
     * @return void
     */
    public function testCheckIfUserExistUserExists(): void
    {
        // create a new user object
        $user = new User();

        // Mock the findOneBy method to return the user object
        $this->userRepository->method('findOneBy')->willReturn($user);

        // Call the checkIfUserExist method with an existing username
        $result = $this->userManager->checkIfUserExist('existing_user');

        // Assert that the result is true
        $this->assertTrue($result);
    }

    /**
     * Test the checkIfUserExist method with a non-existing user.
     *
     * @return void
     */
    public function testCheckIfUserExistUserNotExists(): void
    {
        // Mock the findOneBy method to return null
        $this->userRepository->method('findOneBy')->willReturn(null);

        // Call the checkIfUserExist method with a non-existing username
        $result = $this->userManager->checkIfUserExist('non_existing_user');

        // Assert that the result is false
        $this->assertFalse($result);
    }

    /**
     * Test get user repo method.
     *
     * @return void
     */
    public function testGetUserRepo(): void
    {
        // create a new user object
        $user = new User();
        $this->userRepository->method('findOneBy')->willReturn($user);

        // call the getUserRepo method
        $result = $this->userManager->getUserRepo(['username' => 'testuser']);

        // assert that the result is an instance of the User class
        $this->assertInstanceOf(User::class, $result);
    }

    /**
     * Test get username method.
     *
     * @return void
     */
    public function testGetUsername(): void
    {
        // create a new user object
        $user = new User();
        $user->setUsername('testuser');

        // mock the findOneBy method
        $this->userRepository->method('findOneBy')->willReturn($user);

        // call the getUsername method
        $result = $this->userManager->getUsername(1);

        // assert that the result is equal to 'testuser'
        $this->assertEquals('testuser', $result);
    }

    /**
     * Test get user role method.
     *
     * @return void
     */
    public function testGetUserRole(): void
    {
        // create a new user object
        $user = new User();
        $user->setRole('USER');

        // mock the findOneBy method
        $this->userRepository->method('findOneBy')->willReturn($user);

        // call the getUserRole method
        $result = $this->userManager->getUserRole(1);

        // assert that the result is equal to 'USER'
        $this->assertEquals('USER', $result);
    }

    /**
     * Test check if user is admin method.
     *
     * @return void
     */
    public function testIsUserAdmin(): void
    {
        // create a new user object
        $user = new User();
        $user->setRole('ADMIN');

        // mock the findOneBy method
        $this->userRepository->method('findOneBy')->willReturn($user);

        // call the isUserAdmin method
        $result = $this->userManager->isUserAdmin(1);

        // assert that the result is true
        $this->assertTrue($result);
    }

    /**
     * Test update user role method.
     *
     * @return void
     */
    public function testUpdateUserRole(): void
    {
        // create a new user object
        $user = new User();
        $user->setUsername('testuser');
        $user->setRole('USER');

        // mock the findOneBy method
        $this->userRepository->method('findOneBy')->willReturn($user);

        // mock the flush method
        $this->entityManager->expects($this->once())
            ->method('flush');

        // mock the log method
        $this->logManager->expects($this->once())
            ->method('log')
            ->with('role-granted', 'role admin granted to user: testuser');

        // call the updateUserRole method
        $this->userManager->updateUserRole(1, 'ADMIN');
    }

    /**
     * Test update user role method user not found.
     *
     * @return void
     */
    public function testUpdateUserRoleUserNotFound(): void
    {
        // mock the findOneBy method
        $this->userRepository->method('findOneBy')->willReturn(null);

        // mock the flush method
        $this->entityManager->expects($this->never())
            ->method('flush');

        // mock the log method
        $this->logManager->expects($this->never())
            ->method('log');

        // call the updateUserRole method
        $this->userManager->updateUserRole(1, 'ADMIN');
    }
}
