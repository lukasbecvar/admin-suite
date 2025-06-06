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
        $this->assertSelectorExists('img[alt="Admin Suite Icon"]');
        $this->assertSelectorExists('a:contains("GitHub")');
        $this->assertSelectorExists('a:contains("Author")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
