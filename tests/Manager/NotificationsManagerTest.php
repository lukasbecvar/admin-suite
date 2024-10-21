<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use Doctrine\ORM\EntityRepository;
use App\Manager\NotificationsManager;
use App\Entity\NotificationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NotificationsManagerTest
 *
 * This for the notifications manager class
 *
 * @package App\Tests\Manager
 */
class NotificationsManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private AuthManager & MockObject $authManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private DatabaseManager & MockObject $databaseManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    /** @var EntityRepository<NotificationSubscriber> & MockObject */
    private EntityRepository & MockObject $repositoryMock;

    /** @var NotificationsManager The tested class */
    private NotificationsManager $notificationsManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(EntityRepository::class);

        // create instance of NotificationsManager with mocked dependencies
        $this->notificationsManager = new NotificationsManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->authManagerMock,
            $this->errorManagerMock,
            $this->databaseManagerMock,
            $this->entityManagerMock
        );
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

        // mock entity manager and repository
        $this->entityManagerMock->expects($this->once())->method('getRepository')
            ->with(NotificationSubscriber::class)->willReturn($this->repositoryMock);

        $this->repositoryMock->expects($this->once())->method('findBy')->with(['status' => 'open'])
            ->willReturn($notificationsSubscribers);

        // call method
        $result = $this->notificationsManager->getNotificationsSubscribers('open');

        // check result
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

        // mock entity manager and repository
        $this->entityManagerMock->expects($this->once())->method('getRepository')
            ->with(NotificationSubscriber::class)->willReturn($this->repositoryMock);

        $this->repositoryMock->expects($this->once())->method('findOneBy')->with(['endpoint' => 'endpoint'])
            ->willReturn($notificationsSubscriber);

        // call method
        $result = $this->notificationsManager->getSubscriberIdByEndpoint('endpoint');

        // check result
        $this->assertEquals($notificationsSubscriber->getId(), $result);
    }

    /**
     * Test regenerate vapid keys
     *
     * @return void
     */
    public function testRegenerateVapidKeys(): void
    {
        // mock database manager
        $this->databaseManagerMock->expects($this->once())->method('tableTruncate')
            ->with($this->appUtilMock->getEnvValue('DATABASE_NAME'), 'notifications_subscribers');

        // mock log manager
        $this->logManagerMock->expects($this->once())->method('log')->withConsecutive(
            ['notifications-manager', 'generate vapid keys', LogManager::LEVEL_CRITICAL],
            ['notifications', 'Subscribe push notifications', LogManager::LEVEL_INFO]
        );

        // call method
        $result = $this->notificationsManager->regenerateVapidKeys();

        // check result
        $this->assertNotNull($result);
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
        $this->authManagerMock->expects($this->once())
            ->method('getLoggedUserId')
            ->willReturn($userId);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('flush');
        $this->entityManagerMock->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(NotificationSubscriber::class));

        // mock log manager
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('notifications'),
            $this->equalTo('Subscribe push notifications'),
            $this->equalTo(LogManager::LEVEL_INFO)
        );

        // call method
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
        $subscriberId = 1;
        $newStatus = 'closed';

        // mock notification subscriber
        $notificationSubscriberMock = $this->createMock(NotificationSubscriber::class);
        $notificationSubscriberMock->expects($this->once())->method('setStatus')->with($newStatus);

        // mock entity repository
        $entityRepositoryMock = $this->createMock(EntityRepository::class);
        $entityRepositoryMock->expects($this->once())->method('find')
            ->with($subscriberId)->willReturn($notificationSubscriberMock);

        // mock entity manager to return the repository
        $this->entityManagerMock->expects($this->once())->method('getRepository')
            ->with(NotificationSubscriber::class)->willReturn($entityRepositoryMock);

        // mock entity manager flush
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call method
        $this->notificationsManager->updateNotificationsSubscriberStatus($subscriberId, $newStatus);
    }

    /**
     * Test update notifications subscriber status not found
     *
     * @return void
     */
    public function testUpdateNotificationsSubscriberStatusNotFound(): void
    {
        $subscriberId = 1;
        $newStatus = 'closed';

        // mock entity repository
        $entityRepositoryMock = $this->createMock(EntityRepository::class);
        $entityRepositoryMock->expects($this->once())->method('find')->with($subscriberId)->willReturn(null);

        // mock entity manager to return the repository
        $this->entityManagerMock->expects($this->once())->method('getRepository')
            ->with(NotificationSubscriber::class)->willReturn($entityRepositoryMock);

        // mock error manager
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'Notification subscriber id: ' . $subscriberId . ' not found',
            Response::HTTP_NOT_FOUND
        );

        // call method
        $this->notificationsManager->updateNotificationsSubscriberStatus($subscriberId, $newStatus);
    }

    /**
     * Test update notifications subscriber status exception
     *
     * @return void
     */
    public function testUpdateNotificationsSubscriberStatusException(): void
    {
        $subscriberId = 1;
        $newStatus = 'closed';

        // mock exception scenario
        $this->entityManagerMock->expects($this->once())->method('getRepository')
            ->with(NotificationSubscriber::class)->willThrowException(new \Exception('Database error'));

        // mock error manager for exception handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->equalTo('error to update notifications subscriber status: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call method
        $this->notificationsManager->updateNotificationsSubscriberStatus($subscriberId, $newStatus);
    }

    /**
     * Test send notification with disabled push notifications
     *
     * @return void
     */
    public function testSendNotificationWithDisabledPushNotifications(): void
    {
        // mock app util to return false for push notifications
        $this->appUtilMock->expects($this->once())->method('getEnvValue')
            ->with('PUSH_NOTIFICATIONS_ENABLED')->willReturn('false');

        // call method
        $this->notificationsManager->sendNotification('Test Title', 'Test Message', null);
    }
}
