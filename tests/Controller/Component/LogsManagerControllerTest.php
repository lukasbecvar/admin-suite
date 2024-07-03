<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class LogsManagerControllerTest
 *
 * Test cases for the LogsManagerController actions
 *
 * @package App\Tests\Controller\Component
 */
class LogsManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Test case for loading the logs table page
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
        $this->assertSelectorExists('a[href="/manager/logs/exception/self"]');
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
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test case for loading the self exception logs page
     *
     * @return void
     */
    public function testLoadSelfExceptionLogs(): void
    {
        $this->client->request('GET', '/manager/logs/exception/self');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('span:contains("Exception logs")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
