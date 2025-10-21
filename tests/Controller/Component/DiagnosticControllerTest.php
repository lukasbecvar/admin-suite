<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Controller\Component\DiagnosticController;

/**
 * Class DiagnosticControllerTest
 *
 * Test cases for diagnostic dashboard component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(DiagnosticController::class)]
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
        $this->assertAnySelectorTextContains('p', 'System health and status checks');
        $this->assertSelectorTextContains('body', 'Diagnostics');
        $this->assertSelectorTextContains('body', 'Suite Diagnostics');
        $this->assertSelectorTextContains('body', 'System Diagnostics');
        $this->assertSelectorTextContains('body', 'Suite Diagnostics');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
