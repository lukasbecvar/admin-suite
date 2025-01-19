<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LogoutControllerTest
 *
 * Test cases for logout controller component
 *
 * @package App\Tests\Controller\Auth
 */
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
