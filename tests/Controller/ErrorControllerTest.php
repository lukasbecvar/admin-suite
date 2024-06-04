<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ErrorControllerTest
 *
 * Test the error controller
 *
 * @package App\Tests\Controller
 */
class ErrorControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test error controller with empty code
     *
     * @return void
     */
    public function testErrorEmptyCode(): void
    {
        $this->client->request('GET', '/error');

        // assert response
        $this->assertSelectorTextContains('p', 'Page not found');
        $this->assertSelectorTextContains('p', 'This page was not found');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test error controller with unknown code
     *
     * @return void
     */
    public function testErrorUnknown(): void
    {
        $this->client->request('GET', '/error?code=unknown');

        // assert response
        $this->assertSelectorTextContains('p', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with maintenance code
     *
     * @return void
     */
    public function testErrorMaintenanceUnknown(): void
    {
        $this->client->request('GET', '/error?code=maintenance');

        // assert response
        $this->assertSelectorTextContains('p', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with banned code
     *
     * @return void
     */
    public function testErrorBannedUnknown(): void
    {
        $this->client->request('GET', '/error?code=banned');

        // assert response
        $this->assertSelectorTextContains('p', 'Unknown error');
        $this->assertSelectorTextContains('p', 'Please contact the service administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError400(): void
    {
        $this->client->request('GET', '/error?code=400');

        // assert response
        $this->assertSelectorTextContains('p', 'Bad request error');
        $this->assertSelectorTextContains('p', 'Please try again later or contact the administrator');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError401(): void
    {
        $this->client->request('GET', '/error?code=401');

        // assert response
        $this->assertSelectorTextContains('p', 'Unauthorized');
        $this->assertSelectorTextContains('p', 'You do not have permission to access this page');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError403(): void
    {
        $this->client->request('GET', '/error?code=403');

        // assert response
        $this->assertSelectorTextContains('p', 'Forbidden');
        $this->assertSelectorTextContains('p', 'You do not have permission to access this page');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError404(): void
    {
        $this->client->request('GET', '/error?code=404');

        // assert response
        $this->assertSelectorTextContains('p', 'Page not found');
        $this->assertSelectorTextContains('p', 'This page was not found');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError426(): void
    {
        $this->client->request('GET', '/error?code=426');

        // assert response
        $this->assertSelectorTextContains('p', 'Upgrade Required');
        $this->assertSelectorTextContains('p', 'This website is not available for non https connections');
        $this->assertResponseStatusCodeSame(Response::HTTP_UPGRADE_REQUIRED);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError429(): void
    {
        $this->client->request('GET', '/error?code=429');

        // assert response
        $this->assertSelectorTextContains('p', 'Too Many Requests');
        $this->assertSelectorTextContains('p', 'Please try to wait and try again later');
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Test error controller with invalid code
     *
     * @return void
     */
    public function testError500(): void
    {
        $this->client->request('GET', '/error?code=500');

        // assert response
        $this->assertSelectorTextContains('p', 'Internal Server Error');
        $this->assertSelectorTextContains('p', 'The server encountered an unexpected condition that prevented it from fulfilling the request');
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
