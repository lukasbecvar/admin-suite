<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use App\Controller\Api\LogApiController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogApiControllerTest
 *
 * Test cases the external log API controller endpoint
 *
 * @package App\Tests\Controller
 */
#[CoversClass(LogApiController::class)]
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
     * Test external log request without api key
     *
     * @return void
     */
    public function testExternalLogRequestWithoutToken(): void
    {
        $this->client->request('POST', '/api/external/log');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test external log request with invalid api key
     *
     * @return void
     */
    public function testExternalLogRequestWithInvalidToken(): void
    {
        $this->client->request('POST', '/api/external/log', server: ['HTTP_API_KEY' => 'invalid']);
        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test external log request without parameters
     *
     * @return void
     */
    public function testExternalLogRequestWithoutParameters(): void
    {
        $this->simulateLogin($this->client);
        $this->client->request('POST', '/api/external/log', server: ['HTTP_API_KEY' => 'fba6eb31278954ce68feb303cbd34bfe']);

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
        $this->simulateLogin($this->client);
        $this->client->request('POST', '/api/external/log', [
            'name' => 'external-log',
            'message' => 'test message',
            'level' => 1
        ], server: ['HTTP_API_KEY' => 'fba6eb31278954ce68feb303cbd34bfe']);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertEquals('Log message has been logged', $responseData['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test external log request with XML payload
     *
     * @return void
     */
    public function testExternalLogRequestWithXmlPayload(): void
    {
        $this->simulateLogin($this->client);
        $this->client->request(
            'POST',
            '/api/external/log',
            server: ['HTTP_API_KEY' => 'fba6eb31278954ce68feb303cbd34bfe', 'CONTENT_TYPE' => 'application/xml'],
            content: '<log><name>xml-log</name><message>xml message</message><level>2</level></log>'
        );

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

    /**
     * Test get system logs request with XML format
     *
     * @return void
     */
    public function testGetSystemLogsRequestWithXmlFormat(): void
    {
        $this->simulateLogin($this->client);
        $this->client->request('GET', '/api/system/logs?format=xml');

        // get response
        $response = $this->client->getResponse();

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/xml'),
            'Response should be delivered as XML'
        );

        // assert XML structure
        $xml = simplexml_load_string((string) $response->getContent());
        $this->assertNotFalse($xml);
        $this->assertEquals('systemLogs', $xml->getName());
        $this->assertNotEmpty($xml->from);
        $this->assertNotEmpty($xml->logs);
    }
}
