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
        $this->assertSelectorTextContains('h2', '404 – Page Not Found');
        $this->assertSelectorTextContains('p', "The page you’re looking for doesn’t exist, has been moved, or never existed at all.");
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
        $this->assertSelectorTextContains('h2', '400 – Bad Request');
        $this->assertSelectorTextContains('p', 'Something went wrong with your request. Please try again or contact the administrator if the issue persists.');
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
        $this->assertSelectorTextContains('h2', '401 – Unauthorized');
        $this->assertSelectorTextContains('p', "You don’t have permission to access this page. If you believe this is an error, please contact the administrator.");
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
        $this->assertSelectorTextContains('h2', '403 – Forbidden');
        $this->assertSelectorTextContains('p', "You don’t have permission to view this page.");
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
        $this->assertSelectorTextContains('h2', '404 – Page Not Found');
        $this->assertSelectorTextContains('p', "The page you’re looking for doesn’t exist, has been moved, or never existed at all.");
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
        $this->assertSelectorTextContains('h2', '426 – Upgrade Required');
        $this->assertSelectorTextContains('p', 'This website requires a secure HTTPS connection and modern browser JavaScript features.');
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
        $this->assertSelectorTextContains('h2', '429 – Too Many Requests');
        $this->assertSelectorTextContains('p', "You've made too many requests in a short period of time. Please slow down and try again later.");
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
        $this->assertSelectorTextContains('h2', '500 – Internal Server Error');
        $this->assertSelectorTextContains('p', 'Something went wrong on our side. Please try again later or contact support if the issue persists.');
        $this->assertSelectorExists('a[href="/"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
