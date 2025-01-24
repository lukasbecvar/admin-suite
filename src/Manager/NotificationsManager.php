<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Entity\NotificationSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\NotificationSubscriberRepository;

/**
 * Class NotificationsManager
 *
 * Manager for sending push notifications
 *
 * @package App\Manager
 */
class NotificationsManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private DatabaseManager $databaseManager;
    private EntityManagerInterface $entityManager;
    private NotificationSubscriberRepository $notificationSubscriberRepository;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        DatabaseManager $databaseManager,
        EntityManagerInterface $entityManager,
        NotificationSubscriberRepository $notificationSubscriberRepository
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->databaseManager = $databaseManager;
        $this->notificationSubscriberRepository = $notificationSubscriberRepository;
    }

    /**
     * Check if push notifications is enabled
     *
     * @return bool
     */
    public function checkIsPushNotificationsEnabled(): bool
    {
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') == 'true') {
            return true;
        }
        return false;
    }

    /**
     * Get notification subscribers
     *
     * @param string $status The status of the notifications subscribers
     *
     * @return array<NotificationSubscriber> The notifications subscribers
     */
    public function getNotificationsSubscribers(string $status = 'open'): ?array
    {
        return $this->notificationSubscriberRepository->findBy([
            'status' => $status
        ]);
    }

    /**
     * Get subscriber id by endpoint
     *
     * @param string $endpoint The endpoint of the subscriber
     *
     * @return int|null The subscriber id or null if not found
     */
    public function getSubscriberIdByEndpoint(string $endpoint): ?int
    {
        // get subscriber by endpoint
        $usbscriber = $this->notificationSubscriberRepository->findOneBy([
            'endpoint' => $endpoint
        ]);

        // check if subscriber not found
        if ($usbscriber == null) {
            return null;
        }

        return $usbscriber->getId();
    }

    /**
     * Regenerate VAPID keys
     *
     * @return array<string> The new VAPID keys
     */
    public function regenerateVapidKeys(): array
    {
        // check if push notifications is enabled
        if (!$this->checkIsPushNotificationsEnabled()) {
            $this->errorManager->handleError(
                message: 'Push notifications are disabled',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // generate VAPID keys
            $vapidKeys = VAPID::createVapidKeys();

            // update VAPID keys in .env file
            $this->appUtil->updateEnvValue('PUSH_NOTIFICATIONS_VAPID_PUBLIC_KEY', $vapidKeys['publicKey']);
            $this->appUtil->updateEnvValue('PUSH_NOTIFICATIONS_VAPID_PRIVATE_KEY', $vapidKeys['privateKey']);

            // truncate notofications subscribers table
            $this->databaseManager->tableTruncate($this->appUtil->getEnvValue('DATABASE_NAME'), 'notifications_subscribers');

            // log generate vapid keys event
            $this->logManager->log(
                name: 'notifications-manager',
                message: 'generate vapid keys',
                level: LogManager::LEVEL_CRITICAL
            );

            // return new VAPID keys
            return $vapidKeys;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to generate VAPID keys: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Subscribe push notifications
     *
     * @param string $endpoint The endpoint of the push notifications
     * @param string $publicKey The public key of the push notifications
     * @param string $authToken The auth token of the push notifications
     *
     * @return void
     */
    public function subscribePushNotifications(string $endpoint, string $publicKey, string $authToken): void
    {
        // get subscriber user id
        $userId = $this->authManager->getLoggedUserId();

        // create subscriber entity
        $notoficationSubscriber = new NotificationSubscriber();
        $notoficationSubscriber->setEndpoint($endpoint)
            ->setPublicKey($publicKey)
            ->setAuthToken($authToken)
            ->setSubscribedTime(new DateTime())
            ->setStatus('open')
            ->setUserId($userId);

        try {
            // save subscriber to database
            $this->entityManager->persist($notoficationSubscriber);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to subscribe push notifications: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log subscribe notification event
        $this->logManager->log(
            name: 'notifications',
            message: 'Subscribe push notifications',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Update notifications subscriber status
     *
     * @param int $id The id of the notifications subscriber
     * @param string $status The status of the notifications subscriber
     *
     * @return void
     */
    public function updateNotificationsSubscriberStatus(int $id, string $status): void
    {
        try {
            // get notification subscriber
            $notificationSubscriber = $this->notificationSubscriberRepository->find($id);

            // check if subscriber found
            if ($notificationSubscriber == null) {
                $this->errorManager->handleError(
                    message: 'Notification subscriber id: ' . $id . ' not found',
                    code: Response::HTTP_NOT_FOUND
                );
            } else {
                // update status
                $notificationSubscriber->setStatus($status);
                $this->entityManager->flush();
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update notifications subscriber status: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Send notifications
     *
     * @param string $title The notification title text
     * @param string $message The notification message
     * @param array<NotificationSubscriber>|null $recivers The notifications subscribers
     *
     * @return void
     */
    public function sendNotification(string $title, string $message, ?array $recivers = null): void
    {
        // check if push notifications is enabled
        if (!$this->checkIsPushNotificationsEnabled()) {
            return;
        }

        // check if recivers are set
        if ($recivers == null) {
            $recivers = $this->getNotificationsSubscribers('open');
        }

        // create web push instance
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'subject',
                'publicKey' => $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_VAPID_PUBLIC_KEY'),
                'privateKey' => $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_VAPID_PRIVATE_KEY'),
            ],
        ]);

        // check if recivers are set
        if (is_iterable($recivers)) {
            foreach ($recivers as $reciver) {
                // create subscription object
                $subscription = Subscription::create([
                    'endpoint' => $reciver->getEndpoint(),
                    'publicKey' => $reciver->getPublicKey(),
                    'authToken' => $reciver->getAuthToken(),
                    'contentEncoding' => $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_CONTENT_ENCODING'),
                ]);

                // create notification payload
                $notificationPayload = json_encode([
                    'title' => $title,
                    'body' => $message
                ]);

                // add notification to queue
                if ($notificationPayload) {
                    $webPush->queueNotification($subscription, $notificationPayload, [
                        'TTL' => intval($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_MAX_TTL'))
                    ]);
                }
            }

            // send notifications
            foreach ($webPush->flush() as $report) {
                /** @var \Minishlink\WebPush\MessageSentReport $report */
                $endpoint = $report->getRequest()->getUri()->__toString();
                if (!$report->isSuccess()) {
                    // check response code status
                    $response = $report->getResponse();
                    if ($response !== null && $response->getStatusCode() === 410) {
                        $subscriberId = $this->getSubscriberIdByEndpoint($endpoint);
                        if ($subscriberId !== null) {
                            $this->updateNotificationsSubscriberStatus($subscriberId, 'closed');
                        }
                    }
                }
            }
        }
    }
}
