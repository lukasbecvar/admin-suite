<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Manager\NotificationsManager;
use App\Entity\NotificationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use App\Repository\NotificationSubscriberRepository;

/**
 * Class NotificationsManagerTest
 *
 * Test cases for notification manager
 *
 * @package App\Tests\Manager
 */
class NotificationsManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private AuthManager & MockObject $authManagerMock;
    private NotificationsManager $notificationsManager;
    private ErrorManager & MockObject $errorManagerMock;
    private DatabaseManager & MockObject $databaseManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;
    private NotificationSubscriberRepository & MockObject $repositoryMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(NotificationSubscriberRepository::class);

        // create notifications manager instance
        $this->notificationsManager = new NotificationsManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->authManagerMock,
            $this->errorManagerMock,
            $this->databaseManagerMock,
            $this->entityManagerMock,
            $this->repositoryMock
        );
    }

    /**
     * Test check is push notifications enabled when enabled
     *
     * @return void
     */
    public function testCheckIsPushNotificationsEnabledWhenEnabled(): void
    {
        // simulate PUSH_NOTIFICATIONS_ENABLED
        $this->appUtilMock->expects($this->once())->method('getEnvValue')->willReturn('true');

        // call tested method
        $result = $this->notificationsManager->checkIsPushNotificationsEnabled();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check is push notifications enabled when disabled
     *
     * @return void
     */
    public function testCheckIsPushNotificationsEnabledWhenDisabled(): void
    {
        // simulate PUSH_NOTIFICATIONS_ENABLED
        $this->appUtilMock->expects($this->once())->method('getEnvValue')->willReturn('false');

        // call tested method
        $result = $this->notificationsManager->checkIsPushNotificationsEnabled();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get notifications subscribers
     *
     * @return void
     */
    public function testGetNotificationsSubscribers(): void
    {
        // mock notifications subscribers
        $notificationsSubscribers = [
            new NotificationSubscriber(),
            new NotificationSubscriber(),
        ];
        $this->repositoryMock->expects($this->once())->method('findBy')->with(['status' => 'open'])
            ->willReturn($notificationsSubscribers);

        // call tested method
        $result = $this->notificationsManager->getNotificationsSubscribers('open');

        // assert result
        $this->assertEquals($notificationsSubscribers, $result);
    }

    /**
     * Test get subscriber id by endpoint
     *
     * @return void
     */
    public function testGetSubscriberIdByEndpoint(): void
    {
        // mock notifications subscriber
        $notificationsSubscriber = new NotificationSubscriber();

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with(['endpoint' => 'endpoint'])
            ->willReturn($notificationsSubscriber);

        // call tested method
        $result = $this->notificationsManager->getSubscriberIdByEndpoint('endpoint');

        // assert result
        $this->assertEquals($notificationsSubscriber->getId(), $result);
    }

    /**
     * Test regenerate vapid keys
     *
     * @return void
     */
    public function testRegenerateVapidKeys(): void
    {
        // expect tableTruncate method call
        $this->databaseManagerMock->expects($this->once())->method('tableTruncate')
            ->with($this->appUtilMock->getEnvValue('DATABASE_NAME'), 'notifications_subscribers');

        // expect log manager call
        $this->logManagerMock->expects($this->exactly(1))->method('log')->with(
            $this->equalTo('notifications-manager'),
            $this->equalTo('generate vapid keys'),
            $this->equalTo(LogManager::LEVEL_CRITICAL)
        );

        // call tested method
        $result = $this->notificationsManager->regenerateVapidKeys();

        // assert result
        $this->assertArrayHasKey('publicKey', $result);
        $this->assertArrayHasKey('privateKey', $result);
    }

    /**
     * Test subscribe push notifications
     *
     * @return void
     */
    public function testSubscribePushNotifications(): void
    {
        // mock user id
        $userId = 1;

        // expect getLoggedUserId method call
        $this->authManagerMock->expects($this->once())->method('getLoggedUserId')->willReturn($userId);

        // expect flush and persist methods to be called
        $this->entityManagerMock->expects($this->once())->method('flush');
        $this->entityManagerMock->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(NotificationSubscriber::class));

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('notifications'),
            $this->equalTo('Subscribe push notifications'),
            $this->equalTo(LogManager::LEVEL_INFO)
        );

        // call tested method
        $this->notificationsManager->subscribePushNotifications(
            'test-endpoint',
            'test-publicKey',
            'test-authToken'
        );
    }

    /**
     * Test update notifications subscriber status
     *
     * @return void
     */
    public function testUpdateNotificationsSubscriberStatus(): void
    {
        $subscriber = $this->createMock(NotificationSubscriber::class);

        // mock repository
        $this->repositoryMock->method('find')->with(1)->willReturn($subscriber);

        // expect flush method to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->notificationsManager->updateNotificationsSubscriberStatus(1, 'closed');
    }

    /**
     * Test send notification with disabled push notifications
     *
     * @return void
     */
    public function testSendNotificationWithDisabledPushNotifications(): void
    {
        // mock app util to return false for push notifications enabled status
        $this->appUtilMock->expects($this->once())->method('getEnvValue')
            ->with('PUSH_NOTIFICATIONS_ENABLED')->willReturn('false');

        // call tested method
        $this->notificationsManager->sendNotification('Test Title', 'Test Message', null);
    }
}
