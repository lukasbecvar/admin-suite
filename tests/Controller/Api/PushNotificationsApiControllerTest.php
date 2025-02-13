<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class PushNotificationsApiControllerTest
 *
 * Test cases for notifications API controller endpoints
 *
 * @package App\Tests\Controller\Api
 */
class PushNotificationsApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->simulateLogin($this->client);
    }

    /**
     * Test get push notifications status with status is false
     *
     * @return void
     */
    public function testGetPushNotificationsStatusWhenStatusIsDisabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/enabled');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('false', $responseData['enabled']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test get push notifications public key when push notifications is disabled
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsIsDisabled(): void
    {
        // simulate push notifications disabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/public-key');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('disabled', $responseData['status']);
        $this->assertSame('Push notifications is disabled', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test get push notifications public key when push notifications is enabled
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsEnabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'true';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/public-key');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertNotEmpty($responseData['vapid_public_key']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test subscribe to push notidications when push notifications is disabled
     *
     * @return void
     */
    public function testSubscribeWhenPushNotificationsIsDisabled(): void
    {
        // simulate push notifications disabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make post request to the endpoint
        $this->client->request('POST', '/api/notifications/subscribe', [
            'endpoint' => 'https://chromeapi.test',
            'keys' => [
                'p256dh' => 'p256dh',
                'auth' => 'auth'
            ]
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('disabled', $responseData['status']);
        $this->assertSame('Push notifications is disabled', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test subscribe to push notifications when push notifications is enabled
     *
     * @return void
     */
    public function testSubscribeWhenPushNotificationsIsEnabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'true';

        // subscriber input data
        $subscriber = json_encode([
            'endpoint' => 'https://chromeapi.test',
            'keys' => [
                'p256dh' => 'p256dh',
                'auth' => 'auth'
            ]
        ]);

        // check if subscriber input data is empty
        if (!$subscriber) {
            $this->fail('Subscriber input data is empty');
        }

        // send subscribe request
        $this->client->request('POST', '/api/notifications/subscribe', [], [], ['CONTENT_TYPE' => 'application/json'], $subscriber);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('Subscription received', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test check push notifications subscription when push notifications is disabled
     *
     * @return void
     */
    public function testCheckPushSubscriptionWhenPushNotificationsIsDisabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make get request to the endpoint
        $this->client->request('POST', '/api/notifications/check-push-subscription');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('disabled', $responseData['status']);
        $this->assertSame('Push notifications is disabled', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test check push notifications subscription when push notifications is enabled
     *
     * @return void
     */
    public function testCheckPushSubscriptionWhenPushNotificationsIsEnabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'true';

        // make get request to the endpoint
        $this->client->request('POST', '/api/notifications/check-push-subscription', content: json_encode([
            'endpoint' => 'bakvalanxD'
        ]) ?: null);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
