<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LoginControllerTest
 *
 * Test the login controller.
 *
 * @package App\Tests\Controller\Auth
*/
class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test login page rendering.
     *
     * @return void
     */
    public function testLoginPageRendering(): void
    {
        $this->client->request('GET', '/login');

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test login with valid credentials.
     *
     * @return void
     */
    public function testLoginWithEmptyCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test invalid login.
     *
     * @return void
     */
    public function testInvalidLogin(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill in the form fields with invalid data
        $form['login_form[username]'] = 'invalid_username';
        $form['login_form[password]'] = 'invalid_password';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('.bg-red-700', 'Invalid username or password.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test valid login.
     *
     * @return void
     */
    public function testValidLoginSubmit(): void
    {
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill in the form fields with valid data
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'test';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseRedirects('/');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
