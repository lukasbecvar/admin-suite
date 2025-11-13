<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\User;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Manager\ErrorManager;
use App\Tests\CustomTestCase;
use App\Manager\DatabaseManager;
use App\Manager\NotificationsManager;
use App\Entity\NotificationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\NotificationSubscriberRepository;

/**
 * Class NotificationsManagerTest
 *
 * Test cases for notification manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(NotificationsManager::class)]
class NotificationsManagerTest extends CustomTestCase
{
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private AuthManager & MockObject $authManagerMock;
    private UserManager & MockObject $userManagerMock;
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
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(NotificationSubscriberRepository::class);

        // mock get user reference
        $this->userManagerMock->method('getUserReference')->willReturnCallback(function (int $userId) {
            return $this->createUserEntity($userId);
        });

        // create notifications manager instance
        $this->notificationsManager = new NotificationsManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->authManagerMock,
            $this->userManagerMock,
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
     * Test get notifications subscriber by user id
     *
     * @return void
     */
    public function testGetNotificationsSubscriberByUserId(): void
    {
        // mock notifications subscriber
        $notificationsSubscriber = new NotificationSubscriber();

        // mock find notifications subscriber
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with($this->callback(function (array $criteria) {
            $this->assertSame('open', $criteria['status']);
            $this->assertInstanceOf(User::class, $criteria['user']);
            return true;
        }))->willReturn($notificationsSubscriber);

        // call tested method
        $result = $this->notificationsManager->getNotificationsSubscriberByUserId(1);

        // assert result
        $this->assertEquals($notificationsSubscriber, $result);
    }

    /**
     * Test check if endpoint is subscribed
     *
     * @return void
     */
    public function testCheckIfEndpointIsSubscribed(): void
    {
        // mock notifications subscriber
        $notificationsSubscriber = new NotificationSubscriber();

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with(['endpoint' => 'endpoint', 'status' => 'open'])
            ->willReturn($notificationsSubscriber);

        // call tested method
        $result = $this->notificationsManager->checkIfEndpointIsSubscribed('endpoint');

        // assert result
        $this->assertTrue($result);
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
            'notifications-manager',
            'generate vapid keys',
            LogManager::LEVEL_CRITICAL
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
            'notifications',
            'subscribe push notifications',
            LogManager::LEVEL_INFO
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
        $this->repositoryMock->method('findBy')->willReturn([$subscriber]);

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

    /**
     * Test get notifications subscriber by user id with current logged user
     *
     * @return void
     */
    public function testGetNotificationsSubscriberByUserIdWithCurrentLoggedUser(): void
    {
        // mock current logged user ID
        $userId = 123;
        $this->authManagerMock->expects($this->once())->method('getLoggedUserId')->willReturn($userId);

        // mock notification subscriber
        $subscriber = new NotificationSubscriber();
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with($this->callback(function (array $criteria) {
            $this->assertSame('open', $criteria['status']);
            $this->assertInstanceOf(User::class, $criteria['user']);
            return true;
        }))->willReturn($subscriber);

        // call tested method
        $result = $this->notificationsManager->getNotificationsSubscriberByUserId();

        // assert result
        $this->assertSame($subscriber, $result);
    }

    /**
     * Test get subscriber id by endpoint when subscriber not found
     *
     * @return void
     */
    public function testGetSubscriberIdByEndpointWhenSubscriberNotFound(): void
    {
        // mock repository to return null
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with([
            'endpoint' => 'non-existent-endpoint'
        ])->willReturn(null);

        // call tested method
        $result = $this->notificationsManager->getSubscriberIdByEndpoint('non-existent-endpoint');

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test update notifications subscriber status when no subscribers found
     *
     * @return void
     */
    public function testUpdateNotificationsSubscriberStatusWhenNoSubscribersFound(): void
    {
        // mock repository to return empty array
        $this->repositoryMock->expects($this->once())->method('findBy')->with($this->callback(function (array $criteria) {
            $this->assertInstanceOf(User::class, $criteria['user']);
            return true;
        }))->willReturn([]);

        // expect flush method not to be called
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->notificationsManager->updateNotificationsSubscriberStatus(999, 'closed');
    }

    /**
     * Test update notifications subscriber status with exception
     *
     * @return void
     */
    public function testUpdateNotificationsSubscriberStatusWithException(): void
    {
        // mock subscriber
        $subscriber = $this->createMock(NotificationSubscriber::class);
        $subscriber->expects($this->once())->method('setStatus')->with('closed');

        // mock repository
        $this->repositoryMock->expects($this->once())->method('findBy')->willReturn([$subscriber]);

        // mock entity manager to throw exception
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('Database error'));

        // expect error handler to be called
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update notifications subscriber status: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->notificationsManager->updateNotificationsSubscriberStatus(1, 'closed');
    }

    /**
     * Test update notification subscriber status by ID
     *
     * @return void
     */
    public function testUpdateNotificationSubscriberStatusById(): void
    {
        // mock notification subscriber
        $subscriber = $this->createMock(NotificationSubscriber::class);
        $subscriber->expects($this->once())->method('setStatus')->with('closed');

        // mock find notification subscriber
        $this->repositoryMock->expects($this->once())->method('find')->with(123)->willReturn($subscriber);

        // expect flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->notificationsManager->updateNotificationSubscriberStatusById(123, 'closed');
    }

    /**
     * Test update notification subscriber status by ID when subscriber not found
     *
     * @return void
     */
    public function testUpdateNotificationSubscriberStatusByIdWithMissingSubscriber(): void
    {
        // mock find notification subscriber
        $this->repositoryMock->expects($this->once())->method('find')->with(123)->willReturn(null);

        // expect flush not to be called
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->notificationsManager->updateNotificationSubscriberStatusById(123, 'closed');
    }

    /**
     * Test update notification subscriber status by ID with exception
     *
     * @return void
     */
    public function testUpdateNotificationSubscriberStatusByIdWithException(): void
    {
        // mock notification subscriber
        $subscriber = $this->createMock(NotificationSubscriber::class);
        $subscriber->expects($this->once())->method('setStatus')->with('closed');

        // mock find notification subscriber
        $this->repositoryMock->expects($this->once())->method('find')->with(123)->willReturn($subscriber);

        // mock flush throws exception
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('Database error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update notification subscriber status: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->notificationsManager->updateNotificationSubscriberStatusById(123, 'closed');
    }
}
