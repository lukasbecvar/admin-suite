<?php

namespace App\Tests\Controller;

use App\Tests\CustomTestCase;
use App\Controller\AntiLogController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AntiLogControllerTest
 *
 * Test cases for anti log component
 *
 * @package App\Tests\Controller
 */
#[CoversClass(AntiLogController::class)]
class AntiLogControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test set anti log with user not logged in
     *
     * @return void
     */
    public function testSetAntiLogWithUserNotLoggedIn(): void
    {
        $this->client->request('GET', '/13378/antilog');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test enable anti log
     *
     * @return void
     */
    public function testEnableAntiLog(): void
    {
        // simulate user authentication
        $this->simulateLogin($this->client);

        // create request
        $this->client->request('GET', '/13378/antilog');

        // assert response
        $this->assertResponseRedirects('/manager/logs');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
