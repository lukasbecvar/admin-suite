<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\SystemAuditController;

/**
 * Class SystemAuditControllerTest
 *
 * Test cases for system audit component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(SystemAuditController::class)]
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
        $this->assertAnySelectorTextContains('p', 'System security and audit information');
        $this->assertSelectorTextContains('body', 'System Audit');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="Go to diagnostics"]');
        $this->assertSelectorExists('a[title="Go to metrics dashboard"]');
        $this->assertSelectorTextContains('body', 'Process List');
        $this->assertSelectorTextContains('body', 'Linux System Users');
        $this->assertSelectorTextContains('body', 'Firewall Open Ports (UFW)');
        $this->assertSelectorTextContains('body', 'System Information');
        $this->assertSelectorTextContains('body', 'SSH Access History');
        $this->assertSelectorTextContains('body', 'Journalctl Live Logs');
        $this->assertSelectorTextContains('body', 'System Logs');
        $this->assertSelectorTextContains('body', 'System Diagnostics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
