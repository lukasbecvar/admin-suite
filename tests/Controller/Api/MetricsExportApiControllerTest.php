<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Api\MetricsExportApiController;

/**
 * Class MetricsExportApiControllerTest
 *
 * Test cases for metrics export API endpoint
 *
 * @package App\Tests\Controller\Api
 */
#[CoversClass(MetricsExportApiController::class)]
class MetricsExportApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test only GET is allowed call export endpoint
     *
     * @return void
     */
    public function testExportMetricsWithInvalidMethod(): void
    {
        $this->client->request('POST', '/api/metrics/export');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test requests without authentication are redirected to login
     *
     * @return void
     */
    public function testExportMetricsRequiresLogin(): void
    {
        $this->client->request('GET', '/api/metrics/export');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test JSON response structure for authenticated requests
     *
     * @return void
     */
    public function testExportMetricsReturnsJson(): void
    {
        $this->simulateLogin($this->client);

        $this->client->request('GET', '/api/metrics/export?service_name=host-system&time_period=last_week');

        /** @var array<mixed> $responseData */
        $responseData = json_decode((string) $this->client->getResponse()->getContent(), true);

        // assert response
        $this->assertIsArray($responseData);
        $this->assertSame('host-system', $responseData['service']);
        $this->assertSame('last_week', $responseData['time_period']);
        $this->assertArrayHasKey('generated_at', $responseData);
        $this->assertNotEmpty($responseData['generated_at']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('categories', $responseData['data']);
        $this->assertArrayHasKey('metrics', $responseData['data']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test XML formatting when requested via query parameter
     *
     * @return void
     */
    public function testExportMetricsReturnsXml(): void
    {
        $this->simulateLogin($this->client);

        $this->client->request('GET', '/api/metrics/export?format=xml&time_period=last_24_hours');

        // get response
        $response = $this->client->getResponse();
        $xml = simplexml_load_string((string) $response->getContent());

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertTrue($response->headers->contains('Content-Type', 'application/xml'), 'Response should be delivered as XML');
        $this->assertNotFalse($xml);
        $this->assertEquals('metrics', $xml->getName());
        $this->assertEquals('host-system', (string) $xml->service);
        $this->assertEquals('last_24_hours', (string) $xml->time_period);
    }

    /**
     * Test invalid time periods are rejected with proper error code
     *
     * @return void
     */
    public function testExportMetricsRejectsInvalidTimePeriod(): void
    {
        $this->simulateLogin($this->client);

        $this->client->request('GET', '/api/metrics/export?time_period=invalid_range');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
