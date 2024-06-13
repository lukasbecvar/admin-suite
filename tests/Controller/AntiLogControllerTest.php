<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AntiLogControllerTest
 *
 * Test the anti-log controller
 *
 * @package App\Tests\Controller
 */
class AntiLogControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test if the anti-log page is accessible.
     *
     * @return void
     */
    public function testAntiLogNotLoggedIn(): void
    {
        $this->client->request('GET', '/13378/antilog');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test if the anti-log page is accessible.
     *
     * @return void
     */
    public function testAnitLogEnable(): void
    {
        $this->simulateLogin($this->client);

        // create request
        $this->client->request('GET', '/13378/antilog');

        // assert response
        $this->assertResponseRedirects('/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
