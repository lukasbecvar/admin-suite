<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class LoginControllerTest
 *
 * Test cases for login page auth controller component
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
     * Test load login page
     *
     * @return void
     */
    public function testLoadLoginPage(): void
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
     * Test submit login form with empty credentials
     *
     * @return void
     */
    public function testSubmitLoginFormWithEmptyCredentials(): void
    {
        $crawler = $this->client->request('POST', '/login');

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
     * Test submit login form with invalid credentials
     *
     * @return void
     */
    public function testSubmitLoginFormWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form inputs
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
        $this->assertSelectorTextContains('.bg-red-750', 'Invalid username or password.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with wrong password
     *
     * @return void
     */
    public function testSubmitLoginFormWithWrongPassword(): void
    {
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form inputs
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'fewfewfewfwfewf';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('.bg-red-750', 'Invalid username or password.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login formwith wrong username
     *
     * @return void
     */
    public function testSubmitLoginFormWithWrongUsername(): void
    {
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form inputs
        $form['login_form[username]'] = 'fwewfwfwfewfewf';
        $form['login_form[password]'] = 'test';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Login');
        $this->assertSelectorExists('form[name="login_form"]');
        $this->assertSelectorExists('input[name="login_form[username]"]');
        $this->assertSelectorExists('input[name="login_form[password]"]');
        $this->assertSelectorExists('input[name="login_form[remember]"]');
        $this->assertSelectorTextContains('button', 'Login');
        $this->assertSelectorTextContains('.bg-red-750', 'Invalid username or password.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit login form with valid credentials
     *
     * @return void
     */
    public function testSubmitLoginFormWithValidCredentials(): void
    {
        $crawler = $this->client->request('POST', '/login');

        // get the form
        $form = $crawler->selectButton('Login')->form();

        // fill form inputs
        $form['login_form[username]'] = 'test';
        $form['login_form[password]'] = 'test';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseRedirects('/dashboard');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
