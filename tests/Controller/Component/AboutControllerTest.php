<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AboutControllerTest
 *
 * Test cases for about component
 *
 * @package App\Tests\Controller\Component
 */
class AboutControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test render about page
     *
     * @return void
     */
    public function testRenderAboutPage(): void
    {
        // render about page
        $this->client->request('GET', '/about');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertAnySelectorTextContains('p', 'System information and project details');
        $this->assertAnySelectorTextContains('p', 'Simple & user-friendly solution for monitoring and managing Linux servers, primarily designed for single server instances on Debian-based systems.');
        $this->assertAnySelectorTextContains('h3', 'Quick Links');
        $this->assertAnySelectorTextContains('h3', 'Contact');
        $this->assertAnySelectorTextContains('h3', 'License');
        $this->assertSelectorExists('img[alt="Admin Suite Icon"]');
        $this->assertSelectorExists('a:contains("GitHub")');
        $this->assertSelectorExists('a:contains("Author")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
