<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use App\Controller\ActionRunnerController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class ActionRunnerControllerTest
 *
 * Test cases for action runner component
 *
 * @package App\Tests\Controller
 */
#[CoversClass(ActionRunnerController::class)]
class ActionRunnerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test submit service action runner with invalid method
     *
     * @return void
     */
    public function testSubmitServiceActionRunnerWithInvalidMethod(): void
    {
        // simulate user authentication
        $this->simulateLogin($this->client);

        // create request
        $this->client->request('GET', '/service/action/runner', [
            'service' => 'ufw',
            'action' => 'enable',
            'referer' => 'app_dashboard'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test request to service action runner when not logged in
     *
     * @return void
     */
    public function testRequestToServiceActionRunnerWhenNotLoggedIn(): void
    {
        $this->client->request('POST', '/service/action/runner');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test submit service action runner with success response
     *
     * @return void
     */
    public function testSubmitServiceActionRunnerWithSuccessResponse(): void
    {
        // simulate user authentication
        $this->simulateLogin($this->client);

        // create request
        $this->client->request('POST', '/service/action/runner', [
            'service' => 'ufw',
            'action' => 'enable',
            'referer' => 'app_dashboard'
        ]);

        // assert response
        $this->assertResponseRedirects('/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
