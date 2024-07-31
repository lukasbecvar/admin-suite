<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Entity\Banned;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\BannedRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class BanManagerTest
 *
 * Test the BanManager class
 *
 * @package App\Tests\Manager
 */
class BanManagerTest extends TestCase
{
    /** @var BanManager */
    private BanManager $banManager;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManagerMock;

    /** @var AuthManager|MockObject */
    private AuthManager|MockObject $authManagerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var BannedRepository|MockObject */
    private BannedRepository|MockObject $repositoryMock;

    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    protected function setUp(): void
    {
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->repositoryMock = $this->createMock(BannedRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // entity manager mock
        $this->entityManagerMock
            ->method('getRepository')
            ->willReturn($this->repositoryMock);

        // create the ban manager instance
        $this->banManager = new BanManager(
            $this->logManagerMock,
            $this->userManagerMock,
            $this->authManagerMock,
            $this->errorManagerMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test ban user method
     *
     * @return void
     */
    public function testBanUser(): void
    {
        // mock User entity
        $userId = 1;
        $reason = 'test reason';
        $loggedUserId = 2;
        $loggedUsername = 'admin';

        // mock User entity
        $userMock = $this->createMock(User::class);
        $userMock
            ->method('getUsername')
            ->willReturn($loggedUsername);

        // mock methods
        $this->authManagerMock
            ->method('getLoggedUserId')
            ->willReturn($loggedUserId);

        $this->authManagerMock
            ->method('getLoggedUserRepository')
            ->willReturn($userMock);

        // repository mock
        $this->repositoryMock
            ->method('findOneBy')
            ->with(['banned_user_id' => $userId, 'status' => 'active'])
            ->willReturn(null);

        // log manager mock
        $this->logManagerMock
            ->expects($this->once())
            ->method('log')
            ->with(
                'ban-manager',
                'user: ' . $userId . ' has been banned'
            );

        // call the method
        $this->banManager->banUser($userId, $reason);
    }

    /**
     * Test is user banned
     *
     * @return void
     */
    public function testIsUserBanned(): void
    {
        // test user
        $userId = 1;

        $banned = new Banned();

        // repository mock
        $this->repositoryMock
            ->method('findOneBy')
            ->with(['banned_user_id' => $userId, 'status' => 'active'])
            ->willReturn($banned);

        // get banned status
        $status = $this->banManager->isUserBanned($userId);

        // assert status
        $this->assertTrue($status);
    }

    /**
     * Test get ban reason
     *
     * @return void
     */
    public function testGetBanReason(): void
    {
        // test user
        $userId = 1;
        $reason = 'test reason';

        // set banned data
        $banned = new Banned();
        $banned->setReason($reason);

        // repository mock
        $this->repositoryMock
            ->method('findOneBy')
            ->with(['banned_user_id' => $userId, 'status' => 'active'])
            ->willReturn($banned);

        // assert reason
        $this->assertEquals($reason, $this->banManager->getBanReason($userId));
    }

    /**
     * Test unban user method
     *
     * @return void
     */
    public function testUnBanUser(): void
    {
        // test user
        $userId = 1;
        $loggedUserId = 2;
        $loggedUsername = 'admin';

        // set banned data
        $banned = new Banned();
        $banned->setBannedUserId($userId);
        $banned->setStatus('active');

        // mock user entity
        $userMock = $this->createMock(User::class);
        $userMock
            ->method('getUsername')
            ->willReturn($loggedUsername);

        // mock auth manager
        $this->authManagerMock
            ->method('getLoggedUserId')
            ->willReturn($loggedUserId);

        $this->authManagerMock
            ->method('getLoggedUserRepository')
            ->willReturn($userMock);

        // repository mock
        $this->repositoryMock
            ->method('findOneBy')
            ->with(['banned_user_id' => $userId, 'status' => 'active'])
            ->willReturn($banned);

        // log manager mock
        $this->logManagerMock
            ->expects($this->once())
            ->method('log')
            ->with(
                'ban-manager',
                'user: ' . $userId . ' is unbanned'
            );

        // call the method
        $this->banManager->unBanUser($userId);

        // assert status
        $this->assertEquals('inactive', $banned->getStatus());
    }

    /**
     * Test get banned users method
     *
     * @return void
     */
    public function testGetBannedUsers(): void
    {
        // create test users
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $user3 = $this->createMock(User::class);

        // set user IDs
        $user1->method('getId')->willReturn(1);
        $user2->method('getId')->willReturn(2);
        $user3->method('getId')->willReturn(3);

        // mock UserManager to return the list of users
        $this->userManagerMock
            ->method('getAllUsersRepository')
            ->willReturn([$user1, $user2, $user3]);

        // mock BannedRepository to return banned users based on user IDs
        $this->repositoryMock
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if ($criteria['banned_user_id'] == 1 && $criteria['status'] == 'active') {
                    return new Banned();
                }
                if ($criteria['banned_user_id'] == 3 && $criteria['status'] == 'active') {
                    return new Banned();
                }
                return null;
            });

        // call the method
        $bannedUsers = $this->banManager->getBannedUsers();

        // assert the banned users list
        $this->assertCount(2, $bannedUsers);
        $this->assertContains($user1, $bannedUsers);
        $this->assertContains($user3, $bannedUsers);
    }

    /**
     * Test get banned count
     *
     * @return void
     */
    public function testGetBannedCount(): void
    {
        $output = $this->banManager->getBannedCount();

        // assert output
        $this->assertIsInt($output);
    }
}
