<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class IndexControllerTest
 *
 * Test cases for index controller
 *
 * @package App\Tests\Controller
 */
class IndexControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load index page
     *
     * @return void
     */
    public function testLoadIndexPage(): void
    {
        $this->client->request('GET', '/');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load index page with logged in user
     *
     * @return void
     */
    public function testLoadIndexPageWithLoggedInUser(): void
    {
        // simulate user authentication
        $this->simulateLogin($this->client);

        $this->client->request('GET', '/');

        // assert response
        $this->assertResponseRedirects('/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
