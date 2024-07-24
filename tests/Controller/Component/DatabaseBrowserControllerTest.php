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

    /**
     * Tests that the row add form loads successfully
     *
     * @return void
     */
    public function testLoadRowAddForm(): void
    {
        $this->client->request('GET', '/manager/database/add', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Add row to users');
        $this->assertSelectorTextContains('body', 'Id');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertSelectorTextContains('body', 'Ip_address');
        $this->assertSelectorTextContains('body', 'User_agent');
        $this->assertSelectorTextContains('body', 'Register_time');
        $this->assertSelectorTextContains('body', 'Last_login_time');
        $this->assertSelectorTextContains('body', 'Token');
        $this->assertSelectorTextContains('body', 'Profile_pic');
        $this->assertSelectorTextContains('body', 'Add Row');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit row add form with invalid type
     *
     * @return void
     */
    public function testSubmitRowAddFormInvalidType(): void
    {
        $this->client->request('POST', '/manager/database/add', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',

            // submit form data
            'id' => 'invalid',
            'username' => 'testuser',
            'password' => 'testpassword',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testagent',
            'register_time' => '2022-01-01 00:00:00',
            'last_login_time' => '2022-01-01 00:00:00',
            'token' => 'testtoken',
            'profile_pic' => 'testprofilepic'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'The field id must be a number.');
        $this->assertSelectorTextContains('body', 'Add row to users');
        $this->assertSelectorTextContains('body', 'Id');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertSelectorTextContains('body', 'Ip_address');
        $this->assertSelectorTextContains('body', 'User_agent');
        $this->assertSelectorTextContains('body', 'Register_time');
        $this->assertSelectorTextContains('body', 'Last_login_time');
        $this->assertSelectorTextContains('body', 'Token');
        $this->assertSelectorTextContains('body', 'Profile_pic');
        $this->assertSelectorTextContains('body', 'Add Row');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit row add form with empty value
     *
     * @return void
     */
    public function testSubmitRowAddFormEmptyValue(): void
    {
        $this->client->request('POST', '/manager/database/add', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',

            // submit form data
            'id' => '',
            'username' => 'testuser',
            'password' => 'testpassword',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testagent',
            'register_time' => '2022-01-01 00:00:00',
            'last_login_time' => '2022-01-01 00:00:00',
            'token' => 'testtoken',
            'profile_pic' => 'testprofilepic'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'The field id is required and cannot be empty.');
        $this->assertSelectorTextContains('body', 'Add row to users');
        $this->assertSelectorTextContains('body', 'Id');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertSelectorTextContains('body', 'Ip_address');
        $this->assertSelectorTextContains('body', 'User_agent');
        $this->assertSelectorTextContains('body', 'Register_time');
        $this->assertSelectorTextContains('body', 'Last_login_time');
        $this->assertSelectorTextContains('body', 'Token');
        $this->assertSelectorTextContains('body', 'Profile_pic');
        $this->assertSelectorTextContains('body', 'Add Row');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test successful row add form submission
     *
     * @return void
     */
    public function testSubmitRowAddFormValid(): void
    {
        $this->client->request('POST', '/manager/database/add', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',

            // submit form data
            'id' => random_int(100000000, 1000000000),
            'username' => 'testuser',
            'password' => 'testpassword',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testagent',
            'register_time' => '2022-01-01 00:00:00',
            'last_login_time' => '2022-01-01 00:00:00',
            'role' => 'admin',
            'token' => 'testtoken',
            'profile_pic' => 'testprofilepic'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test successful row delete form submission
     *
     * @return void
     */
    public function testDeleteRowSuccess(): void
    {
        $this->client->request('GET', '/manager/database/delete', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',
            'id' => 5
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Tests that the table truncate confirmation page loads successfully
     *
     * @return void
     */
    public function testLoadTableTruncateConfirmation(): void
    {
        $this->client->request('GET', '/manager/database/truncate', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'logs'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Truncate table logs');
        $this->assertSelectorTextContains('body', 'YES');
        $this->assertSelectorTextContains('body', 'NO');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test successful table truncate form submission
     *
     * @return void
     */
    public function testSubmitTableTruncateConfirmation(): void
    {
        $this->client->request('GET', '/manager/database/truncate', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'logs',
            'confirm' => 'yes'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
