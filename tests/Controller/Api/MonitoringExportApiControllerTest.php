<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Api\MonitoringExportApiController;

/**
 * Class MonitoringExportApiControllerTest
 *
 * Test cases for monitoring export API
 *
 * @package App\Tests\Controller\Api
 */
#[CoversClass(MonitoringExportApiController::class)]
class MonitoringExportApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test only GET method is allowed
     *
     * @return void
     */
    public function testExportMonitoringWithInvalidMethod(): void
    {
        $this->client->request('POST', '/api/monitoring/export');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test authentication is required for the endpoint
     *
     * @return void
     */
    public function testExportMonitoringRequiresLogin(): void
    {
        $this->client->request('GET', '/api/monitoring/export');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test JSON payload contains monitoring snapshot
     *
     * @return void
     */
    public function testExportMonitoringReturnsJson(): void
    {
        $this->simulateLogin($this->client);
        $this->client->request('GET', '/api/monitoring/export');

        /** @var array<mixed> $responseData */
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        // assert response
        $this->assertArrayHasKey('services', $responseData);
        $this->assertSame('SSHD', $responseData['services'][0]['display_name']);
        $this->assertArrayHasKey('monitoring_logs', $responseData);
        $this->assertArrayHasKey('sla_history', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test XML payload formatting
     *
     * @return void
     */
    public function testExportMonitoringReturnsXml(): void
    {
        $this->simulateLogin($this->client);
        $this->client->request('GET', '/api/monitoring/export?format=xml&logs_limit=10');

        // get response
        $response = $this->client->getResponse();
        $xml = simplexml_load_string((string) $response->getContent());

        // assert response
        $this->assertTrue($response->headers->contains('Content-Type', 'application/xml'), 'Response should be delivered as XML');
        $this->assertNotFalse($xml);
        $this->assertEquals('monitoring', $xml->getName());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
