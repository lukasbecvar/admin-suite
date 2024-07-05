<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class DiagnosticControllerTest
 *
 * This test verifies that the diagnostic page loads correctly and displays the expected content
 *
 * @package App\Tests\Controller\Component
 */
class DiagnosticControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Tests that the diagnostic page loads successfully and contains the expected content
     *
     * @return void
     */
    public function testLoadDiagnosticPage(): void
    {
        $this->client->request('GET', '/diagnostic');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Website diagnostics');
        $this->assertSelectorTextContains('body', 'System diagnostics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
