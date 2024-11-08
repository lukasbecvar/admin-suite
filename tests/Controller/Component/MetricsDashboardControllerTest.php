<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class MetricsDashboardControllerTest
 *
 * Test cases for the MetricsDashboardController actions
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
     * Test case for loading the metrics dashboard page
     *
     * @return void
     */
    public function testLoadMetricsDashboardPage(): void
    {
        $this->client->request('GET', '/metrics/dashboard');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
