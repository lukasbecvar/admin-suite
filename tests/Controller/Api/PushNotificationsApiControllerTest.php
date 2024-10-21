<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class PushNotificationsApiControllerTest
 *
 * Test for push notifications API
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
     * Test the get enabled status
     *
     * @return void
     */
    public function testGetPushNotificationsEnabledStatus(): void
    {
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';
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
     * Test the get enabled status with push notifications enabled
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsDisabled(): void
    {
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';
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
     * Test the get vapid keys
     *
     * @return void
     */
    public function testGetPublicKeyWithPushNotificationsEnabled(): void
    {
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'true';
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
     * Test the subscriber with push notifications disabled
     *
     * @return void
     */
    public function testSubscriberWithPushNotificationsDisabled(): void
    {
        $_ENV['PUSH_NOTIFICATIONS_ENABLED'] = 'false';
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
     * Test the subscribe endpoint with push notifications enabled
     *
     * @return void
     */
    public function testSubscriberWithPushNotificationsEnabled(): void
    {
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
