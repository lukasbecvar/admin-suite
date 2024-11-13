<?php

namespace App\Tests\Controller\Api;

use App\Exception\AppErrorException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LogApiControllerTest
 *
 * Test cases the external log API controller endpoint
 *
 * @package App\Tests\Controller
 */
class LogApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test the external log request with invalid method
     *
     * @return void
     */
    public function testExternalLogRequestWithInvalidMethod(): void
    {
        // expect exception
        $this->expectException(AppErrorException::class);

        // make GET request to the external log endpoint
        $this->client->request('GET', '/api/external/log');
    }

    /**
     * Test the external log request without token
     *
     * @return void
     */
    public function testExternalLogRequestWithoutToken(): void
    {
        // make request to the external log endpoint
        $this->client->request('POST', '/api/external/log');

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        /** @var array<string> $responseData */
        $responseData = json_decode($responseContent, true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEquals('Access token is not set', $responseData['error']);
    }

    /**
     * Test the external log request with invalid token
     *
     * @return void
     */
    public function testExternalLogRequestWithInvalidToken(): void
    {
        // make request to the external log endpoint
        $this->client->request('POST', '/api/external/log', [
            'token' => 'invalid'
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
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertEquals('Access token is invalid', $responseData['error']);
    }

    /**
     * Test the external log request without parameters
     *
     * @return void
     */
    public function testExternalLogRequestWithoutParameters(): void
    {
        // make request to the external log endpoint
        $this->client->request('POST', '/api/external/log', [
            'token' => $_ENV['EXTERNAL_API_LOG_TOKEN']
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
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertEquals('Parameters name, message and level are required', $responseData['error']);
    }

    /**
     * Test the external log request with valid parameters
     *
     * @return void
     */
    public function testExternalLogRequestWithValidParameters(): void
    {
        // make request to the external log endpoint
        $this->client->request('POST', '/api/external/log', [
            'token' => $_ENV['EXTERNAL_API_LOG_TOKEN'],
            'name' => 'external-log',
            'message' => 'test message',
            'level' => 1
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
        $this->assertEquals('Log message has been logged', $responseData['success']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
