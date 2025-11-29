<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Component\DashboardController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DashboardControllerTest
 *
 * Test cases for dashboard component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(DashboardController::class)]
class DashboardControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load dashboard page
     *
     * @return void
     */
    public function testLoadDashboardPage(): void
    {
        $this->client->request('GET', '/dashboard');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorExists('button[title="Logout user"]');
        $this->assertSelectorExists('a[href="/settings"]');
        $this->assertSelectorExists('a[href="/about"]');
        $this->assertSelectorExists('form[action="/logout"]');
        $this->assertSelectorExists('aside[id="sidebar"]');
        $this->assertSelectorExists('img[alt="profile picture"]');
        $this->assertSelectorExists('h3[id="username"]');
        $this->assertSelectorExists('span[id="role"]');
        $this->assertSelectorExists('a[href="/dashboard"]');
        $this->assertSelectorExists('a[href="/manager/logs"]');
        $this->assertSelectorExists('a[href="/manager/database"]');
        $this->assertSelectorExists('a[href="/metrics/dashboard"]');
        $this->assertSelectorExists('a[href="/manager/monitoring"]');
        $this->assertSelectorExists('a[href="/diagnostic"]');
        $this->assertSelectorExists('a[href="/filesystem"]');
        $this->assertSelectorExists('a[href="/terminal"]');
        $this->assertSelectorExists('a[href="/manager/todo"]');
        $this->assertSelectorExists('a[href="/manager/users"]');
        $this->assertSelectorExists('main[id="main-content"]');
        $this->assertSelectorTextContains('body', 'Diagnostic Alerts');
        $this->assertSelectorTextContains('body', 'Process List');
        $this->assertSelectorTextContains('body', 'Monitoring');
        $this->assertSelectorTextContains('body', 'Network Usage');
        $this->assertSelectorTextContains('body', 'System Resources');
        $this->assertSelectorTextContains('body', 'System Information');
        $this->assertSelectorTextContains('body', 'Database Statistics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
