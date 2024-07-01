<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AboutControllerTest
 *
 * This test verifies that the about page loads correctly and displays the expected content
 *
 * @package App\Tests\Controller\Component
 */
class AboutControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Tests that the about page loads successfully and contains the expected content
     *
     * @return void
     */
    public function testLoadAboutPage(): void
    {
        $this->client->request('GET', '/about');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('h1', 'ADMIN-SUITE');
        $this->assertSelectorTextContains('p', $_ENV['APP_VERSION']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
