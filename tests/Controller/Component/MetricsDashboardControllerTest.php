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
        $this->assertSelectorTextContains('body', 'CPU Usage (current)');
        $this->assertSelectorTextContains('body', 'CPU Usage (history)');
        $this->assertSelectorTextContains('body', 'RAM Usage (current)');
        $this->assertSelectorTextContains('body', 'RAM Usage (history)');
        $this->assertSelectorTextContains('body', 'Storage Usage (current)');
        $this->assertSelectorTextContains('body', 'Storage Usage (history)');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
