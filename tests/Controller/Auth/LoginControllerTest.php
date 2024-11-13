<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LoginControllerTest
 *
 * Test cases for login page auth controller actions
 *
 * @package App\Tests\Controller\Auth
*/
class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test login page rendering
     *
     * @return void
     */
    public function testLoginPageRendering(): void
    {
        // request for load login page
        $this->client->request('GET', '/login');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
    }

    /**
     * Test submit login form with empty credentials
     *
     * @return void
     */
    public function testLoginWithEmptyCredentials(): void
    {
        // request for load login page
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
    }

    /**
     * Test submit login form with invalid credentials
     *
     * @return void
     */
    public function testInvalidLogin(): void
    {
        // request for load login page
        $crawler = $this->client->request('GET', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form with invalid credentials
        $form['login_form[username]'] = 'invalid_username';
        $form['login_form[password]'] = 'invalid_password';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('.bg-red-700', 'Invalid username or password.');
    }

    /**
     * Test submit login form with valid credentials
     *
     * @return void
     */
    public function testValidLoginSubmit(): void
    {
        // request for load login page
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form with valid credentials
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'test';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/');
    }
}
