<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\DatabaseBrowserController;

/**
 * Class DatabaseBrowserControllerTest
 *
 * Test cases for database browser component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(DatabaseBrowserController::class)]
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
     * Test load databases list page
     *
     * @return void
     */
    public function testLoadDatabasesListPage(): void
    {
        $this->client->request('GET', '/manager/database');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorExists('a[href="/manager/database?database=' . $_ENV['DATABASE_NAME'] . '"]');
        $this->assertSelectorTextContains('body', 'Databases');
        $this->assertSelectorTextContains('body', 'Database');
        $this->assertSelectorTextContains('body', 'Tables');
        $this->assertSelectorTextContains('body', 'Size (MB)');
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertSelectorTextContains('body', 'Version');
        $this->assertSelectorTextContains('body', 'Uptime');
        $this->assertSelectorTextContains('body', 'Threads connected');
        $this->assertSelectorTextContains('body', 'Max connections');
        $this->assertSelectorTextContains('body', 'Queries');
        $this->assertSelectorTextContains('body', 'Slow queries');
        $this->assertSelectorTextContains('body', 'InnoDB buffer pool');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load tables list page
     *
     * @return void
     */
    public function testLoadTablesListPage(): void
    {
        $this->client->request('GET', '/manager/database', [
            'database' => $_ENV['DATABASE_NAME']
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorExists('a[href="/manager/database/dump"]');
        $this->assertSelectorExists('a[href="/manager/database/console"]');
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorTextContains('Table', 'Table');
        $this->assertSelectorTextContains('Table', 'Rows');
        $this->assertSelectorTextContains('Table', 'Size (MB)');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load tables list page when database not found
     *
     * @return void
     */
    public function testLoadTablesListWhenDatabaseNotFound(): void
    {
        $this->client->request('GET', '/manager/database', [
            'database' => 'blblablanonexistdatabaseokokcsmuckmuckxoxo'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorExists('a[href="/manager/database/dump"]');
        $this->assertSelectorExists('a[href="/manager/database/console"]');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorTextContains('body', 'No tables found');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load table browser page
     *
     * @return void
     */
    public function testLoadTableBrowserPage(): void
    {
        $this->client->request('GET', '/manager/database/table', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertSelectorExists('a[href="/manager/database?database=' . $_ENV['DATABASE_NAME'] . '"]');
        $this->assertSelectorExists('a[href="/manager/database/add?database=' . $_ENV['DATABASE_NAME'] . '&table=users"]');
        $this->assertSelectorExists('a[href="/manager/database?database=' . $_ENV['DATABASE_NAME'] . '"]');
        $this->assertSelectorExists('a[href="/manager/database/truncate?database=' . $_ENV['DATABASE_NAME'] . '&table=users"]');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorExists('a[href="/manager/database?database=' . $_ENV['DATABASE_NAME'] . '"]');
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
        $this->assertSelectorExists('a[href="/manager/database/edit?database=' . $_ENV['DATABASE_NAME'] . '&table=users&page=1&id=1"]');
        $this->assertSelectorExists('button[data-url="/manager/database/delete?database=' . $_ENV['DATABASE_NAME'] . '&table=users&page=1&id=1"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load table truncate confirmation page
     *
     * @return void
     */
    public function testLoadTableTruncateConfirmationPage(): void
    {
        $this->client->request('GET', '/manager/database/truncate', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'logs'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/database/table?database=' . $_ENV['DATABASE_NAME'] . '&table=logs"]');
        $this->assertSelectorTextContains('body', 'Truncate Table');
        $this->assertSelectorTextContains('body', 'Warning:');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit table truncate confirmation
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

    /**
     * Test load row add form
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
        $this->assertSelectorExists('a[href="/manager/database/table?database=' . $_ENV['DATABASE_NAME'] . '&table=users"]');
        $this->assertSelectorTextContains('body', 'Add Row to users');
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
     * Test submit row add form with invalid type field
     *
     * @return void
     */
    public function testSubmitRowAddFormInvalidTypeField(): void
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
        $this->assertSelectorExists('a[href="/manager/database/table?database=' . $_ENV['DATABASE_NAME'] . '&table=users"]');
        $this->assertSelectorTextContains('body', 'The field id must be a number.');
        $this->assertSelectorTextContains('body', 'Add Row to users');
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
     * Test submit row add form with empty value field
     *
     * @return void
     */
    public function testSubmitRowAddFormWithEmptyValue(): void
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
        $this->assertSelectorExists('a[href="/manager/database/table?database=' . $_ENV['DATABASE_NAME'] . '&table=users"]');
        $this->assertSelectorTextContains('body', 'The field id is required and cannot be empty.');
        $this->assertSelectorTextContains('body', 'Add Row to users');
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
     * Test submit row add form with success response
     *
     * @return void
     */
    public function testSubmitRowAddFormWithSuccessResponse(): void
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
            'profile_pic' => 'testprofilepic',
            'allow_api_access' => true
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test successful row delete request
     *
     * @return void
     */
    public function testDeleteRowRequestWithSuccessResponse(): void
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
     * Test delete row request when database not found
     *
     * @return void
     */
    public function testDeleteRowRequestWhenDatabaseNotFound(): void
    {
        $this->client->request('GET', '/manager/database/delete', [
            'database' => 'blblablanonexistdatabaseokokcsmuckmuckxoxo',
            'table' => 'users',
            'id' => 5
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test delete row request when table not found
     *
     * @return void
     */
    public function testDeleteRowRequestWhenTableNotFound(): void
    {
        $this->client->request('GET', '/manager/database/delete', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'blblablanonexistdatabaseokokcsmuckmuckxoxo',
            'id' => 5
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test delete row request when id is not found
     *
     * @return void
     */
    public function testDeleteRowRequestWhenIdIsNotFound(): void
    {
        $this->client->request('GET', '/manager/database/delete', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',
            'id' => 53243324
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test load row edit form
     *
     * @return void
     */
    public function testLoadRowEditForm(): void
    {
        $this->client->request('GET', '/manager/database/edit', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',
            'id' => 6
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Edit Row 6 in users');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertSelectorTextContains('body', 'Ip_address');
        $this->assertSelectorTextContains('body', 'User_agent');
        $this->assertSelectorTextContains('body', 'Register_time');
        $this->assertSelectorTextContains('body', 'Last_login_time');
        $this->assertSelectorTextContains('body', 'Token');
        $this->assertSelectorTextContains('body', 'Profile_pic');
        $this->assertSelectorTextContains('body', 'Update Row');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit edit form with empty value field
     *
     * @return void
     */
    public function testSubmitEditFormWithEmptyValueField(): void
    {
        $this->client->request('POST', '/manager/database/edit', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',
            'id' => 6,

            // form data
            'username' => '',
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
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'The field username is required and cannot be empty.');
        $this->assertSelectorTextContains('body', 'Edit Row 6 in users');
        $this->assertSelectorTextContains('body', 'Username');
        $this->assertSelectorTextContains('body', 'Password');
        $this->assertSelectorTextContains('body', 'Ip_address');
        $this->assertSelectorTextContains('body', 'User_agent');
        $this->assertSelectorTextContains('body', 'Register_time');
        $this->assertSelectorTextContains('body', 'Last_login_time');
        $this->assertSelectorTextContains('body', 'Token');
        $this->assertSelectorTextContains('body', 'Profile_pic');
        $this->assertSelectorTextContains('body', 'Update Row');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit row edit form with success response
     *
     * @return void
     */
    public function testSubmitRowEditFormWithSuccessResponse(): void
    {
        $this->client->request('POST', '/manager/database/edit', [
            'database' => $_ENV['DATABASE_NAME'],
            'table' => 'users',
            'id' => 6,

            // submit form data
            'username' => 'testuser: ' . ByteString::fromRandom(6),
            'password' => 'testpassword',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testagent',
            'register_time' => '2022-01-01 00:00:00',
            'last_login_time' => '2022-01-01 00:00:00',
            'role' => 'admin',
            'token' => ByteString::fromRandom(16),
            'profile_pic' => 'testprofilepic'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load database dump page
     *
     * @return void
     */
    public function testLoadDumpPage(): void
    {
        $this->client->request('GET', '/manager/database/dump', [
            'database' => $_ENV['DATABASE_NAME'],
            'select' => 'yes'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Database Dump');
        $this->assertSelectorTextContains('body', 'Structure');
        $this->assertSelectorTextContains('body', 'Data');
        $this->assertSelectorTextContains('body', $_ENV['DATABASE_NAME']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load database dump page when select not found
     *
     * @return void
     */
    public function testLoadDumpPageNotFoundSelect(): void
    {
        $this->client->request('GET', '/manager/database/dump', [
            'database' => $_ENV['DATABASE_NAME'],
            'plain' => 'yes',
            'select' => 'no'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load database console page
     *
     * @return void
     */
    public function testLoadDatabaseConsolePage(): void
    {
        $this->client->request('GET', '/manager/database/console');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Database Console');
        $this->assertSelectorTextContains('body', 'Execute Query');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit database console query with empty query
     *
     * @return void
     */
    public function testSubmitDatabaseConsoleQueryWithEmptyQuery(): void
    {
        $this->client->request('POST', '/manager/database/console', [
            'query_console_form' => [
                'query' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Database Console');
        $this->assertSelectorTextContains('body', 'Execute Query');
        $this->assertSelectorTextContains('body', 'Please enter a query');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit database console query with success response
     *
     * @return void
     */
    public function testSubmitDatabaseConsoleQueryWithSuccessResponse(): void
    {
        $this->client->request('POST', '/manager/database/console', [
            'query_console_form' => [
                'query' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Database Console');
        $this->assertSelectorTextContains('body', 'Execute Query');
        $this->assertSelectorTextContains('body', 'Please enter a query');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
