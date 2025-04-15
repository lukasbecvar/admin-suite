<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogApiControllerTest
 *
 * Test cases the external log API controller endpoint
 *
 * @package App\Tests\Controller
 */
class LogApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test external log request with invalid method
     *
     * @return void
     */
    public function testExternalLogRequestWithInvalidMethod(): void
    {
        $this->client->request('GET', '/api/external/log');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test external log request without token
     *
     * @return void
     */
    public function testExternalLogRequestWithoutToken(): void
    {
        $this->client->request('POST', '/api/external/log');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('Parameter "token" is required', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test external log request with invalid token
     *
     * @return void
     */
    public function testExternalLogRequestWithInvalidToken(): void
    {
        $this->client->request('POST', '/api/external/log', [
            'token' => 'invalid'
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('Access token is invalid', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test external log request without parameters
     *
     * @return void
     */
    public function testExternalLogRequestWithoutParameters(): void
    {
        $this->client->request('POST', '/api/external/log', [
            'token' => $_ENV['EXTERNAL_API_LOG_TOKEN']
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('Parameters name, message and level are required', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test external log request with valid parameters
     *
     * @return void
     */
    public function testExternalLogRequestWithValidParameters(): void
    {
        $this->client->request('POST', '/api/external/log', [
            'token' => $_ENV['EXTERNAL_API_LOG_TOKEN'],
            'name' => 'external-log',
            'message' => 'test message',
            'level' => 1
        ]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('Log message has been logged', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test get system logs request with invalid method
     *
     * @return void
     */
    public function testGetSystemLogsRequestWithInvalidMethod(): void
    {
        $this->client->request('POST', '/api/system/logs');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test get system logs request when user is not logged in
     *
     * @return void
     */
    public function testGetSystemLogsRequestWhenUserIsNotLoggedIn(): void
    {
        $this->client->request('GET', '/api/system/logs');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test get system logs request with success response
     *
     * @return void
     */
    public function testGetSystemLogsRequestWithSuccessResponse(): void
    {
        // simulate login
        $this->simulateLogin($this->client);

        $this->client->request('GET', '/api/system/logs?token');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertArrayHasKey('from', $responseData);
        $this->assertArrayHasKey('to', $responseData);
        $this->assertArrayHasKey('logs', $responseData);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
