<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class MonitoringManagerControllerTest
 *
 * Test for monitoring dashboard component
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
        $this->assertSelectorTextContains('body', 'Internal services');
        $this->assertSelectorTextContains('body', 'HTTP services');
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
        $this->assertSelectorTextContains('body', 'Services config');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
