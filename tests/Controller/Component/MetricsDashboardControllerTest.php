<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class MetricsDashboardControllerTest
 *
 * Test cases for the metrics dashboard component
 *
 * @package App\Tests\Controller\Component
 */
class MetricsDashboardControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load metrics dashboard page
     *
     * @return void
     */
    public function testLoadMetricsDashboardPage(): void
    {
        $this->client->request('GET', '/metrics/dashboard');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorTextContains('body', 'Current usage');
        $this->assertSelectorTextContains('body', 'CPU');
        $this->assertSelectorTextContains('body', 'RAM');
        $this->assertSelectorTextContains('body', 'Storage');
        $this->assertSelectorTextContains('body', 'Cpu usage (history)');
        $this->assertSelectorTextContains('body', 'Ram usage (history)');
        $this->assertSelectorTextContains('body', 'Storage usage (history)');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load service metrics page
     *
     * @return void
     */
    public function testLoadServiceMetricsPage(): void
    {
        $this->client->request('GET', '/metrics/service?service_name=host-system');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorTextContains('body', 'host-system - Cpu usage');
        $this->assertSelectorTextContains('body', 'host-system - Ram usage');
        $this->assertSelectorTextContains('body', 'host-system - Storage usage');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load delete metric confirmation page
     *
     * @return void
     */
    public function testLoadDeleteMetricConfirmationPage(): void
    {
        $this->client->request('GET', '/metrics/delete?service_name=becvar.xyz&metric_name=cpu_usage&confirm=none');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorTextContains('body', 'Metric delete confirmation');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test delete metrics
     *
     * @return void
     */
    public function testDeleteMetrics(): void
    {
        $this->client->request('GET', '/metrics/delete?service_name=becvar.xyz&metric_name=cpu_usage&confirm=yes');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
