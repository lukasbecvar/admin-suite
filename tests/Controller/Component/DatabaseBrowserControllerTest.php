<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DatabaseBrowserControllerTest
 *
 * This test verifies that the database browser page loads correctly and displays the expected content
 *
 * @package App\Tests\Controller\Component
 */
class DatabaseBrowserControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Tests that the database list page loads successfully and contains the expected content
     *
     * @return void
     */
    public function testLoadDatabaseList(): void
    {
        $this->client->request('GET', '/manager/database');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Databases');
        $this->assertSelectorTextContains('body', 'Database');
        $this->assertSelectorTextContains('body', 'Tables');
        $this->assertSelectorTextContains('body', 'Size (MB)');
        $this->assertSelectorTextContains('body', 'admin_suite');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
