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
    private BanManager $banManager;
    private LogManager & MockObject $logManagerMock;
    private UserManager & MockObject $userManagerMock;
    private AuthManager & MockObject $authManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private BannedRepository & MockObject $banRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->banRepositoryMock = $this->createMock(BannedRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // entity manager mock
        $this->entityManagerMock->method('getRepository')->willReturn($this->banRepositoryMock);

        // create the ban manager instance
        $this->banManager = new BanManager(
            $this->logManagerMock,
            $this->userManagerMock,
            $this->authManagerMock,
            $this->errorManagerMock,
            $this->banRepositoryMock,
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
        $userMock->method('getUsername')->willReturn($loggedUsername);

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($loggedUserId);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($userMock);

        // repository mock
        $this->banRepositoryMock->method('findOneBy')
            ->with(['banned_user_id' => $userId, 'status' => 'active'])->willReturn(null);

        // log manager mock
        $this->logManagerMock->expects($this->once())->method('log')->with(
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
        // get banned status
        $status = $this->banManager->isUserBanned(1);

        // assert status
        $this->assertIsBool($status);
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

        // mockování repository
        $this->banRepositoryMock->method('isBanned')
            ->with($userId)
            ->willReturn(true);

        // mockování metody getBanReason tak, aby vracela důvod banu (string)
        $this->banRepositoryMock->method('getBanReason')
            ->with($userId)
            ->willReturn($reason); // zde vracíme string místo celého objektu

        // assert reason
        $this->assertEquals($reason, $this->banRepositoryMock->getBanReason($userId));
    }

    /**
     * Test unban user method
     *
     * @return void
     */
    public function testUnbanUser(): void
    {
        $userId = 1;
        $loggedUserId = 2;
        $loggedUsername = 'admin';

        // mock banned entity
        $banned = new Banned();
        $banned->setBannedUserId($userId);
        $banned->setStatus('active');

        // mock user
        $userMock = $this->createMock(User::class);
        $userMock->method('getUsername')->willReturn($loggedUsername);

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($loggedUserId);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($userMock);

        // mock ban repository
        $this->banRepositoryMock->method('isBanned')
            ->with($userId)
            ->willReturn(true);

        // assert that updateBanStatus is called with 'inactive'
        $this->banRepositoryMock->expects($this->once())
            ->method('updateBanStatus')
            ->with($userId, 'inactive');

        // assert that log is called
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'ban-manager',
            'user: ' . $userId . ' is unbanned'
        );

        // call the method
        $this->banManager->unBanUser($userId);
    }

    /**
     * Test get banned users method
     *
     * @return void
     */
    public function testGetBannedUsers(): void
    {
        // call the method
        $banList = $this->banManager->getBannedUsers();

        // assert the result type
        $this->assertIsArray($banList);
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
