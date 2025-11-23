<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\MetricsDashboardController;

/**
 * Class MetricsDashboardControllerTest
 *
 * Test cases for metrics dashboard component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(MetricsDashboardController::class)]
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
        $this->assertAnySelectorTextContains('p', 'System performance metrics');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="Go to monitoring"]');
        $this->assertSelectorExists('button[title="Aggregate old metrics"]');
        $this->assertSelectorTextContains('body', 'Current Usage');
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
        $this->assertSelectorExists('a[title="Back to monitoring"]');
        $this->assertSelectorTextContains('body', 'host-system - Cpu usage');
        $this->assertSelectorTextContains('body', 'host-system - Ram usage');
        $this->assertSelectorTextContains('body', 'host-system - Storage usage');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test delete metrics
     *
     * @return void
     */
    public function testDeleteMetricsRequest(): void
    {
        $this->client->request('GET', '/metrics/delete?service_name=becvar.xyz&metric_name=cpu_usage');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test aggregate metrics
     *
     * @return void
     */
    public function testAggregateMetrics(): void
    {
        $this->client->request('POST', '/metrics/aggregate');

        // assert response is redirect back to dashboard
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/metrics/dashboard');
    }
}
