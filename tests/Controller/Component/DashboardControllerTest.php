<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DashboardControllerTest
 *
 * Test for dashboard component
 *
 * @package App\Tests\Controller\Component
 */
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
        $this->assertSelectorExists('a[title="Logout user"]');
        $this->assertSelectorExists('a[href="/logout"]');
        $this->assertSelectorExists('aside[id="sidebar"]');
        $this->assertSelectorExists('img[alt="profile picture"]');
        $this->assertSelectorExists('div[class="username font-bold text-xl"]');
        $this->assertSelectorExists('div[class="role text-base"]');
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
        $this->assertSelectorExists('a[href="/account/settings"]');
        $this->assertSelectorExists('main[id="main-content"]');
        $this->assertSelectorTextContains('body', 'Diagnostic alerts');
        $this->assertSelectorTextContains('body', 'Process list');
        $this->assertSelectorTextContains('body', 'Monitoring');
        $this->assertSelectorTextContains('body', 'System Information');
        $this->assertSelectorTextContains('body', 'System Resources');
        $this->assertSelectorTextContains('body', 'Logs');
        $this->assertSelectorTextContains('body', 'Users');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
