<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LogoutControllerTest
 *
 * Test the logout controller
 *
 * @package App\Tests\Controller\Auth
 */
class LogoutControllerTest extends WebTestCase
{
    /**
     * Test user logout
     *
     * @return void
     */
    public function testLogout(): void
    {
        $client = static::createClient();

        // logout request
        $client->request('GET', '/logout');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/login');

        // follow redirect
        $client->followRedirect();

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
