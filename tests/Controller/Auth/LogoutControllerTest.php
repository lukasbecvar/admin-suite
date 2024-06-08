<?php

namespace App\Tests\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LogoutControllerTest extends WebTestCase
{
    public function testLogout(): void
    {
        $client = static::createClient();

        $userCredentials = ['username' => 'test_user', 'password' => 'test_password'];
        $this->fakeLoginUser($client, $userCredentials);

        $client->request('GET', '/logout');

        $this->assertResponseRedirects('/login');

        $client->followRedirect();
        $this->assertSelectorTextContains('div', 'Login');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * @param KernelBrowser $client
     * @param array<string> $userCredentials
     *
     * @return void
     */
    private function fakeLoginUser(KernelBrowser $client, array $userCredentials): void
    {
        $client->request('GET', '/login');
        $client->submitForm('Login', [
            'login_form[username]' => $userCredentials['username'],
            'login_form[password]' => $userCredentials['password'],
        ]);
    }
}
