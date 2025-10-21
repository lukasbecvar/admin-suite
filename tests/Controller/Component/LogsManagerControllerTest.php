<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\LogsManagerController;

/**
 * Class LogsManagerControllerTest
 *
 * Test cases for logs manager component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(LogsManagerController::class)]
class LogsManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load logs table page
     *
     * @return void
     */
    public function testLoadLogsTable(): void
    {
        $this->client->request('GET', '/manager/logs');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/logs/set/readed"]');
        $this->assertSelectorExists('a[href="/manager/logs/system"]');
        $this->assertSelectorExists('a[href="/manager/logs/exception/files"]');
        $this->assertSelectorExists('a[href="/13378/antilog?state=enable"]');
        $this->assertSelectorExists('select[name="filter"]');
        $this->assertSelectorExists('th:contains("#")');
        $this->assertSelectorExists('th:contains("Name")');
        $this->assertSelectorExists('th:contains("Message")');
        $this->assertSelectorExists('th:contains("Time")');
        $this->assertSelectorExists('th:contains("Browser")');
        $this->assertSelectorExists('th:contains("OS")');
        $this->assertSelectorExists('th:contains("IP Address")');
        $this->assertSelectorExists('th:contains("User")');
        $this->assertSelectorExists('a[title="Mark as readed"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load exception files page
     *
     * @return void
     */
    public function testLoadExceptionFiles(): void
    {
        $this->client->request('GET', '/manager/logs/exception/files');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/logs"]');
        $this->assertSelectorTextContains('body', 'Exception Files');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load server logs page
     *
     * @return void
     */
    public function testLoadServerLogsFiles(): void
    {
        $this->client->request('GET', '/manager/logs/system');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/manager/logs"]');
        $this->assertSelectorTextContains('body', 'System Logs');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test request to set logs as readed
     *
     * @return void
     */
    public function testRequestLogsSetReaded(): void
    {
        $this->client->request('GET', '/manager/logs/set/readed');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
