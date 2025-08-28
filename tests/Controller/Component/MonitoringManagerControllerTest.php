<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class MonitoringManagerControllerTest
 *
 * Test cases for monitoring dashboard component
 *
 * @package App\Tests\Controller\Component
 */
class MonitoringManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load monitoring dashboard page
     *
     * @return void
     */
    public function testLoadMonitoringManagerPage(): void
    {
        $this->client->request('GET', '/manager/monitoring');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Monitoring');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="Database status"]');
        $this->assertSelectorExists('a[title="Metrics dashboard"]');
        $this->assertSelectorExists('a[title="Services config"]');
        $this->assertSelectorTextContains('body', 'Internal Services');
        $this->assertSelectorTextContains('body', 'HTTP Services');
        $this->assertSelectorTextContains('body', 'SLA History');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load monitoring config page
     *
     * @return void
     */
    public function testLoadMonitoringConfigPage(): void
    {
        $this->client->request('GET', '/manager/monitoring/config');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to monitoring dashboard"]');
        $this->assertSelectorTextContains('body', 'Services Configuration');
        $this->assertSelectorTextContains('body', 'Service Name:');
        $this->assertSelectorTextContains('body', 'Type:');
        $this->assertSelectorTextContains('body', 'Display Name:');
        $this->assertSelectorTextContains('body', 'Display:');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load service detail page
     *
     * @return void
     */
    public function testLoadServiceDetailPage(): void
    {
        $this->client->request('GET', '/manager/monitoring/service?service_name=apache2');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to monitoring dashboard"]');
        $this->assertSelectorTextContains('body', 'Service monitoring details');
        $this->assertSelectorTextContains('body', 'Service Information');
        $this->assertSelectorTextContains('body', 'Configuration');
        $this->assertSelectorTextContains('body', 'SLA History');
        $this->assertSelectorTextContains('body', 'Configuration Files');
        $this->assertSelectorTextContains('body', 'Service Actions');
        $this->assertSelectorExists('a:contains("Restart Service")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load service detail page with metrics
     *
     * @return void
     */
    public function testLoadServiceDetailPageWithMetrics(): void
    {
        $this->client->request('GET', '/manager/monitoring/service?service_name=becvar.xyz');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Service monitoring details');
        $this->assertSelectorTextContains('body', 'HTTP Service Details');
        $this->assertSelectorTextContains('body', 'URL:');
        $this->assertSelectorTextContains('body', 'Max Response Time:');
        $this->assertSelectorTextContains('body', 'SLA History');
        $this->assertSelectorTextContains('body', 'Service Metrics');
        $this->assertSelectorTextContains('body', 'Service Visitors');
        $this->assertSelectorTextContains('body', 'Referers');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test service detail page with invalid service name
     *
     * @return void
     */
    public function testServiceDetailPageWithInvalidServiceName(): void
    {
        $this->client->request('GET', '/manager/monitoring/service?service_name=invalid_service');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
