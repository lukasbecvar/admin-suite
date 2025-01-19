<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TerminalApiControllerTest
 *
 * Test cases for terminal component
 *
 * @package App\Tests\Controller\Component
 */
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
        $this->assertSelectorTextContains('body', 'Terminal');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('input[id="command"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
