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
     * Test get request for push notifications enabled status with status is false
     *
     * @return void
     */
    public function testGetPushNotificationsEnabledStatus(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/enabled');

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('false', $responseData['enabled']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test get request for push notifications public key get when push notifications is disabled
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsDisabled(): void
    {
        // simulate push notifications disabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/public-key');

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertSame('disabled', $responseData['status']);
        $this->assertSame('Push notifications is disabled', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test get request for push notifications public key get
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsEnabled(): void
    {
        // simulate push notifications enabled
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'true';

        // make get request to the endpoint
        $this->client->request('GET', '/api/notifications/public-key');

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertNotEmpty($responseData['vapid_public_key']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test request for subscribing to push notidications with push notifications disabled
     *
     * @return void
     */
    public function testSubscriberWithPushNotificationsDisabled(): void
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertSame('disabled', $responseData['status']);
        $this->assertSame('Push notifications is disabled', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test request for subscribing to push notifications
     *
     * @return void
     */
    public function testSubscriberWithPushNotificationsEnabled(): void
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertSame('success', $responseData['status']);
        $this->assertSame('Subscription received', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
