<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DashboardControllerTest
 *
 * Test the dashboard page
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
     * Test the dashboard page
     *
     * @return void
     */
    public function testLoadDashboard(): void
    {
        $this->client->request('GET', '/dashboard');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('button[id="menu-toggle"]');
        $this->assertSelectorExists('a[href="/logout"]');
        $this->assertSelectorExists('aside[id="sidebar"]');
        $this->assertSelectorExists('div[class="profile-image mt-2"]');
        $this->assertSelectorExists('div[class="username font-bold text-xl"]');
        $this->assertSelectorExists('div[class="role text-base"]');
        $this->assertSelectorExists('a[href="/dashboard"]');
        $this->assertSelectorExists('a[href="/account/settings"]');
        $this->assertSelectorExists('main[id="main-content"]');
        $this->assertSelectorExists('a[href="/about"]');
        $this->assertSelectorTextContains('body', 'Warnings');
        $this->assertSelectorTextContains('body', 'Process list');
        $this->assertSelectorTextContains('body', 'Services');
        $this->assertSelectorTextContains('body', 'System Information');
        $this->assertSelectorTextContains('body', 'System resources');
        $this->assertSelectorTextContains('body', 'Logs');
        $this->assertSelectorTextContains('body', 'Users');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
