<?php

namespace App\Tests\Controller\Auth;

use App\Tests\CustomTestCase;
use App\Controller\Auth\LogoutController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogoutControllerTest
 *
 * Test cases for logout controller component
 *
 * @package App\Tests\Controller\Auth
 */
#[CoversClass(LogoutController::class)]
class LogoutControllerTest extends CustomTestCase
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
        $client->request('POST', '/logout', [
            'csrf_token' => $this->getCsrfToken($client)
        ]);

        // assert response
        $this->assertResponseRedirects('/login');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
