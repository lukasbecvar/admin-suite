<?php

namespace App\Controller\Api;

use Exception;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Annotation\CsrfProtection;
use App\Manager\NotificationsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class PushNotificationsApiController
 *
 * Controller for push notifications API
 *
 * @package App\Controller\Api
 */
class PushNotificationsApiController extends AbstractController
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;
    private NotificationsManager $notificationsManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, NotificationsManager $notificationsManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
        $this->notificationsManager = $notificationsManager;
    }

    /**
     * API to get push notifications enabled/disabled status
     *
     * This endpoint is used in account settings
     *
     * @return JsonResponse The status response in json
     */
    #[Route('/api/notifications/enabled', methods: ['GET'], name: 'api_notifications_get_enabled_status')]
    public function getPushNotificationsEnabledStatus(): JsonResponse
    {
        // get notifications enabled status
        $status = $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED');

        // return status response
        return $this->json([
            'status' => 'success',
            'enabled' => $status
        ], JsonResponse::HTTP_OK);
    }

    /**
     * API to get VAPID public key
     *
     * This endpoint is used in account settings
     *
     * @return JsonResponse The json response with the public key
     */
    #[Route('/api/notifications/public-key', methods: ['GET'], name: 'api_notifications_get_vapid_public_key')]
    public function getVapidPublicKey(): JsonResponse
    {
        // check if push notifications is enabled
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') != 'true') {
            return $this->json([
                'status' => 'disabled',
                'message' => 'Push notifications is disabled'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            // get vapid public key from env config
            $vapidPublicKey = $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_VAPID_PUBLIC_KEY');

            // return vapid public key
            return $this->json([
                'status' => 'success',
                'vapid_public_key' => $vapidPublicKey
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            // get error message
            $message = $this->appUtil->isDevMode() ? $e->getMessage() : 'Error to get VAPID public key';

            // return error response
            return $this->json([
                'status' => 'error',
                'message' => $message
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API to subscribe push notifications
     *
     * This endpoint is used in account settings
     *
     * Request body parameters:
     *  - endpoint: push notifications endpoint (string)
     *  - keys.p256dh: push notifications p256dh key (string)
     *  - keys.auth: push notifications auth key (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The response with the status of the subscription
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/api/notifications/subscribe', methods: ['POST'], name: 'api_notifications_subscriber')]
    public function subscribePushNotifications(Request $request): JsonResponse
    {
        // check if push notifications is enabled
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') != 'true') {
            return $this->json([
                'status' => 'disabled',
                'message' => 'Push notifications is disabled'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            /** @var array<string> get request data */
            $data = json_decode($request->getContent(), true);

            // validate input data
            if (!isset($data['endpoint']) || !isset($data['keys']['p256dh']) || !isset($data['keys']['auth'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid subscription data'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // get subscription data
            $subscription = [
                'endpoint' => $data['endpoint'],
                'keys' => [
                    'p256dh' => $data['keys']['p256dh'],
                    'auth' => $data['keys']['auth']
                ]
            ];

            // save subscription to database
            $this->notificationsManager->subscribePushNotifications(
                $subscription['endpoint'],
                $subscription['keys']['p256dh'],
                $subscription['keys']['auth']
            );

            // return response
            return $this->json([
                'status' => 'success',
                'message' => 'Subscription received'
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            // get error message
            $message = $this->appUtil->isDevMode() ? $e->getMessage() : 'Error to subscribe push notifications';

            // log error to exception log
            $this->errorManager->logError(
                message: $message,
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error response
            return $this->json([
                'status' => 'error',
                'message' => $message
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API to check if push notifications subscription is active
     *
     * This endpoint is used in account settings
     *
     * Request body parameters:
     *  - endpoint: push notifications endpoint (string)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The response with the status of the subscription
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/api/notifications/check-push-subscription', name: 'api_notifications_check_push_subscription', methods: ['POST'])]
    public function checkPushSubscription(Request $request): JsonResponse
    {
        // check if push notifications is enabled
        if ($this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED') != 'true') {
            return $this->json([
                'status' => 'disabled',
                'message' => 'Push notifications is disabled'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        // get request data
        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? null;

        // check if endpoint is set
        if (!$endpoint) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid request'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if endpoint is subscribed
        if ($this->notificationsManager->checkIfEndpointIsSubscribed($endpoint)) {
            return $this->json([
                'status' => 'success',
                'message' => 'Your subscription is active',
                'subscriber_id' => $this->notificationsManager->getSubscriberIdByEndpoint($endpoint)
            ], JsonResponse::HTTP_OK);
        }

        // return default response
        return $this->json([
            'status' => 'error',
            'message' => 'Your subscription is not registred on the server'
        ], JsonResponse::HTTP_OK);
    }
}
