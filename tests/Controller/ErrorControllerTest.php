<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ErrorControllerTest
 *
 * Test cases for error controller
 *
 * @package App\Tests\Controller
 */
class ErrorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test load error page with empty code
     *
     * @return void
     */
    public function testLoadErrorPageWithEmptyCode(): void
    {
        $this->client->request('GET', '/error');

        // assert response
        $this->assertSelectorTextContains('h2', 'Page not found');
        $this->assertSelectorTextContains('p', "The page you are looking for doesn't exist or has been moved");
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test error controller with unknown code
     *
     * @return void
     */
    public function testLoadErrorUnknownPage(): void
    {
        $this->client->request('GET', '/error?code=unknown');

        // assert response
        $this->assertSelectorTextContains('h2', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with maintenance code
     *
     * @return void
     */
    public function testLoadErrorPageMaintenanceUnknown(): void
    {
        $this->client->request('GET', '/error?code=maintenance');

        // assert response
        $this->assertSelectorTextContains('h2', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with banned code
     *
     * @return void
     */
    public function testLoadErrorPageBannedUnknown(): void
    {
        $this->client->request('GET', '/error?code=banned');

        // assert response
        $this->assertSelectorTextContains('h2', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with 400 code
     *
     * @return void
     */
    public function testLoadErrorPage400(): void
    {
        $this->client->request('GET', '/error?code=400');

        // assert response
        $this->assertSelectorTextContains('h2', 'Bad request error');
        $this->assertSelectorTextContains('p', 'Please try again later or contact the administrator');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test error controller with 401 code
     *
     * @return void
     */
    public function testLoadErrorPage401(): void
    {
        $this->client->request('GET', '/error?code=401');

        // assert response
        $this->assertSelectorTextContains('h2', 'Unauthorized');
        $this->assertSelectorTextContains('p', 'You do not have permission to access this page');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test error controller with 403 code
     *
     * @return void
     */
    public function testLoadErrorPage403(): void
    {
        $this->client->request('GET', '/error?code=403');

        // assert response
        $this->assertSelectorTextContains('h2', 'Forbidden');
        $this->assertSelectorTextContains('p', 'You do not have permission to access this page');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test error controller with 404 code
     *
     * @return void
     */
    public function testLoadErrorPage404(): void
    {
        $this->client->request('GET', '/error?code=404');

        // assert response
        $this->assertSelectorTextContains('h2', 'Page not found');
        $this->assertSelectorTextContains('p', "The page you are looking for doesn't exist or has been moved");
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test error controller with 426 code
     *
     * @return void
     */
    public function testLoadErrorPage426(): void
    {
        $this->client->request('GET', '/error?code=426');

        // assert response
        $this->assertSelectorTextContains('h2', 'Upgrade Required');
        $this->assertSelectorTextContains('p', 'This website is not available over non-HTTPS connections, or your browser does not support JavaScript');
        $this->assertResponseStatusCodeSame(Response::HTTP_UPGRADE_REQUIRED);
    }

    /**
     * Test error controller with 429 code
     *
     * @return void
     */
    public function testLoadErrorPage429(): void
    {
        $this->client->request('GET', '/error?code=429');

        // assert response
        $this->assertSelectorTextContains('h2', 'Too Many Requests');
        $this->assertSelectorTextContains('p', 'Please try to wait and try again later');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Test error controller with 500 code
     *
     * @return void
     */
    public function testLoadErrorPage500(): void
    {
        $this->client->request('GET', '/error?code=500');

        // assert response
        $this->assertSelectorTextContains('h2', 'Internal Server Error');
        $this->assertSelectorTextContains('p', 'Unexpected server-side error');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
