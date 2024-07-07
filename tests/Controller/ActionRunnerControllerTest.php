<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class ActionRunnerControllerTest
 *
 * Test the action runner controller
 *
 * @package App\Tests\Controller
 */
class ActionRunnerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test if the service action runner component with not logged in response
     *
     * @return void
     */
    public function testServiceActionRunnerNotLoggedIn(): void
    {
        $this->client->request('GET', '/service/action/runner');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test if the service action runner component with success response
     *
     * @return void
     */
    public function testServiceActionRunnerSuccess(): void
    {
        $this->simulateLogin($this->client);

        // create request
        $this->client->request('GET', '/service/action/runner', [
            'service' => 'ufw',
            'action' => 'enable',
            'referer' => 'app_dashboard'
        ]);

        // assert response
        $this->assertResponseRedirects('/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
