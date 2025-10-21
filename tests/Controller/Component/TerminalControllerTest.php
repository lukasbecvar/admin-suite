<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Component\TerminalController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TerminalApiControllerTest
 *
 * Test cases for terminal component
 *
 * @package App\Tests\Controller\Component
 */
#[CoversClass(TerminalController::class)]
class TerminalControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load terminal page
     *
     * @return void
     */
    public function testLoadTerminalPage(): void
    {
        $this->client->request('GET', '/terminal');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertAnySelectorTextContains('p', 'Interactive command line interface');
        $this->assertSelectorTextContains('body', 'Terminal');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('input[id="command"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
