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
 * Test cases for ban manager
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

        // mock ban repository
        $this->entityManagerMock->method('getRepository')->willReturn($this->banRepositoryMock);

        // create ban manager instance
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
     * Test ban user
     *
     * @return void
     */
    public function testBanUser(): void
    {
        $userId = 1;
        $reason = 'test reason';
        $loggedUserId = 2;
        $loggedUsername = 'admin';

        // mock user entity
        $userMock = $this->createMock(User::class);
        $userMock->method('getUsername')->willReturn($loggedUsername);

        // mock get logged user repository
        $this->authManagerMock->method('getLoggedUserId')->willReturn($loggedUserId);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($userMock);

        // mock ban repository
        $this->banRepositoryMock->method('findOneBy')->with([
            'banned_user_id' => $userId,
            'status' => 'active'
        ])->willReturn(null);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'ban-manager',
            'user: ' . $userId . ' has been banned'
        );

        // call tested method
        $this->banManager->banUser($userId, $reason);
    }

    /**
     * Test check is user banned
     *
     * @return void
     */
    public function testIsUserBanned(): void
    {
        // testing user id
        $userId = 1;

        // mock ban status
        $this->banRepositoryMock->expects($this->once())->method('isBanned')->with($userId)->willReturn(true);

        // call tested method
        $result = $this->banManager->isUserBanned($userId);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test get ban reason
     *
     * @return void
     */
    public function testGetBanReason(): void
    {
        $userId = 1;
        $reason = 'test reason';

        // mock ban status
        $this->banRepositoryMock->method('isBanned')->with($userId)->willReturn(true);

        // mock get ban reason
        $this->banRepositoryMock->method('getBanReason')->with($userId)->willReturn($reason);

        // call tested method
        $result = $this->banManager->getBanReason($userId);

        // assert result
        $this->assertEquals($reason, $result);
    }

    /**
     * Test unban user
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

        // mock user entity object
        $userMock = $this->createMock(User::class);
        $userMock->method('getUsername')->willReturn($loggedUsername);

        // mock get logged user repository
        $this->authManagerMock->method('getLoggedUserId')->willReturn($loggedUserId);
        $this->authManagerMock->method('getLoggedUserRepository')->willReturn($userMock);

        // mock ban status
        $this->banRepositoryMock->method('isBanned')->with($userId)->willReturn(true);

        // expect update ban status call
        $this->banRepositoryMock->expects($this->once())->method('updateBanStatus')->with($userId, 'inactive');

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'ban-manager',
            'user: ' . $userId . ' is unbanned'
        );

        // call tested method
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

        // assert result
        $this->assertIsArray($banList);
    }

    /**
     * Test get banned count
     *
     * @return void
     */
    public function testGetBannedCount(): void
    {
        $bannedCount = 5;

        // mock count get
        $this->banRepositoryMock->expects($this->once())->method('count')->with([
            'status' => 'active'
        ])->willReturn($bannedCount);

        // call tested method
        $result = $this->banManager->getBannedCount();

        // assert result
        $this->assertEquals($bannedCount, $result);
    }
}
