<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DiagnosticControllerTest
 *
 * Test cases for diagnostic dashboard component
 *
 * @package App\Tests\Controller\Component
 */
class DiagnosticControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load diagnostic dashboard page
     *
     * @return void
     */
    public function testLoadDiagnosticDashboardPage(): void
    {
        $this->client->request('GET', '/diagnostic');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Diagnostics');
        $this->assertSelectorTextContains('body', 'Suite diagnostics');
        $this->assertSelectorTextContains('body', 'System diagnostics');
        $this->assertSelectorTextContains('body', 'Services diagnostics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
