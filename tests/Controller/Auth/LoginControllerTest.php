<?php

namespace App\Tests\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginPageRendering(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Login');
    }

    public function testValidLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('POST', '/login');

        $form = $crawler->selectButton('Login')->form();

        // fill in the form fields with valid data
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'test';

        // submit the form
        $client->submit($form);

        // check if the client was redirected to the index page
        $this->assertResponseRedirects('/');
    }

    public function testInvalidLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Login')->form();

        // fill in the form fields with invalid data
        $form['login_form[username]'] = 'invalid_username';
        $form['login_form[password]'] = 'invalid_password';

        // submit the form
        $client->submit($form);

        // check if the client stayed on the login page
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.bg-red-700', 'Invalid username or password.');
    }
}
