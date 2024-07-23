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
     * Tests that the database list page loads successfully
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
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Tests that the tables list page loads successfully
     *
     * @return void
     */
    public function testLoadTablesList(): void
    {
        $this->client->request('GET', '/manager/database', [
            'database' => $_ENV['DATABASE_NAME']
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertSelectorTextContains('Table', 'Size (MB)');
        $this->assertSelectorTextContains('body', 'users');
        $this->assertSelectorTextContains('body', 'logs');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Tests that the tables list page when database
     *
     * @return void
     */
    public function testLoadTablesListNotFoundDatabase(): void
    {
        $this->client->request('GET', '/manager/database', [
            'database' => 'blblablanonexistdatabaseokokcsmuckmuckxoxo'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'No tables found');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Tests that the table browser page loads successfully
     *
     * @return void
     */
    public function testLoadTableBrowser(): void
    {
        $this->client->request('GET', '/manager/database/table', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'users');
        $this->assertSelectorTextContains('body', 'id');
        $this->assertSelectorTextContains('body', 'username');
        $this->assertSelectorTextContains('body', 'password');
        $this->assertSelectorTextContains('body', 'ip_address');
        $this->assertSelectorTextContains('body', 'user_agent');
        $this->assertSelectorTextContains('body', 'register_time');
        $this->assertSelectorTextContains('body', 'last_login_time');
        $this->assertSelectorTextContains('body', 'token');
        $this->assertSelectorTextContains('body', 'profile_pic');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
