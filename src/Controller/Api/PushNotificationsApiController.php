<?php

namespace App\Controller\Api;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Manager\NotificationsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class PushNotificationsApiController
 *
 * This controller provides API functions for push notifications
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
     * Get the enabled status of push notifications
     *
     * @return JsonResponse The response with the status of push notifications
     */
    #[Route('/api/notifications/enabled', methods: ['GET'], name: 'api_notifications_get_enabled_status')]
    public function getPushNotificationsEnabledStatus(): JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'enabled' => $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_ENABLED')
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Get the VAPID public key
     *
     * @return JsonResponse The response with the VAPID public key
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
            // get vapid public key
            $vapidPublicKey = $this->appUtil->getEnvValue('PUSH_NOTIFICATIONS_VAPID_PUBLIC_KEY');

            // return vapid public key
            return new JsonResponse([
                'status' => 'success',
                'vapid_public_key' => $vapidPublicKey
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API for subscribing to push notifications
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The response with the status of the subscription
     */
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
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid subscription data'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // get subscription data
            $subscription = [
                'endpoint' => $data['endpoint'],
                'keys' => [
                    'p256dh' => $data['keys']['p256dh'],
                    'auth' => $data['keys']['auth'],
                ],
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
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to subscribe push notifications: ' . $e->getMessage(),
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error response
            return $this->json([
                'status' => 'error',
                'message' => 'Error to subscribe push notifications'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
