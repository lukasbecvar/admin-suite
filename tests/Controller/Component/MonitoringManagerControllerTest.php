<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class MonitoringManagerControllerTest
 *
 * Test cases for the MonitoringManagerController actions
 *
 * @package App\Tests\Controller\Component
 */
class MonitoringManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Test case for loading the monitoring manager page
     *
     * @return void
     */
    public function testLoadMonitoringManagerPage(): void
    {
        $this->client->request('GET', '/manager/monitoring');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Monitoring');
        $this->assertSelectorTextContains('body', 'Internal services');
        $this->assertSelectorTextContains('body', 'HTTP services');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test case for loading the monitoring config page
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
