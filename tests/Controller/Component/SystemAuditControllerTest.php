<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TerminalApiControllerTest
 *
 * Test cases for system audit component
 *
 * @package App\Tests\Controller\Component
 */
class SystemAuditControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load system audit page
     *
     * @return void
     */
    public function testLoadSystemAuditPage(): void
    {
        $this->client->request('GET', '/system/audit');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'System Audit');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="Go to diagnostics"]');
        $this->assertSelectorExists('a[title="Go to metrics dashboard"]');
        $this->assertSelectorTextContains('body', 'Process list');
        $this->assertSelectorTextContains('body', 'Linux system users');
        $this->assertSelectorTextContains('body', 'Firewall open ports (ufw)');
        $this->assertSelectorTextContains('body', 'System information');
        $this->assertSelectorTextContains('body', 'SSH access history');
        $this->assertSelectorTextContains('body', 'System logs');
        $this->assertSelectorTextContains('body', 'System diagnostics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
