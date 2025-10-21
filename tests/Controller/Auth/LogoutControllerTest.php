<?php

namespace App\Tests\Controller\Auth;

use App\Controller\Auth\LogoutController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LogoutControllerTest
 *
 * Test cases for logout controller component
 *
 * @package App\Tests\Controller\Auth
 */
#[CoversClass(LogoutController::class)]
class LogoutControllerTest extends WebTestCase
{
    /**
     * Test user logout action
     *
     * @return void
     */
    public function testUserLogoutAction(): void
    {
        $client = static::createClient();

        // logout request
        $client->request('GET', '/logout');

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
