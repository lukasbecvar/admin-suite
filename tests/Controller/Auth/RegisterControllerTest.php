<?php

namespace App\Tests\Controller\Auth;

use App\Manager\UserManager;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class RegisterControllerTest
 *
 * Test cases for admin page controller actions
 *
 * @package App\Tests\Controller\Auth
 */
class RegisterControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        // mock user manager (enable user registration component)
        $mockUserManager = $this->createMock(UserManager::class);
        $mockUserManager->method('isUsersEmpty')->willReturn(true);
        $this->client->getContainer()->set(UserManager::class, $mockUserManager);
    }

    /**
     * Test render register page
     *
     * @return void
     */
    public function testRenderRegisterPage(): void
    {
        $this->client->request('GET', '/register');

        // assert response
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorTextContains('button', 'Register');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with invalid credentials length
     *
     * @return void
     */
    public function testSubmitRegisterFormWithInvalidLength(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // get the form
        $form = $crawler->selectButton('Register')->form();

        // fill form inputs
        $form['registration_form[username]'] = 'a';
        $form['registration_form[password][first]'] = 'a';
        $form['registration_form[password][second]'] = 'a';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorTextContains('button', 'Register');
        $this->assertSelectorTextContains('li:contains("Your username should be at least 3 characters")', 'Your username should be at least 3 characters');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with not match passwords
     *
     * @return void
     */
    public function testSubmitRegisterFormWithNotMatchPasswords(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // get the form
        $form = $crawler->selectButton('Register')->form();

        // fill form inputs
        $form['registration_form[username]'] = 'valid-testing-username';
        $form['registration_form[password][first]'] = 'passwordookokok';
        $form['registration_form[password][second]'] = 'passwordookokok1';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorTextContains('button', 'Register');
        $this->assertSelectorTextContains('li:contains("The values do not match.")', 'The values do not match.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with empty credentials
     *
     * @return void
     */
    public function testSubmitRegisterFormWithEmptyCredentials(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // get the form
        $form = $crawler->selectButton('Register')->form();

        // fill form inputs
        $form['registration_form[username]'] = '';
        $form['registration_form[password][first]'] = '';
        $form['registration_form[password][second]'] = '';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertSelectorTextContains('h2', 'Register');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorTextContains('button', 'Register');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit register form with valid credentials
     *
     * @return void
     */
    public function testSubmitRegisterFormWithSuccessResponse(): void
    {
        $crawler = $this->client->request('GET', '/register');

        // get the form
        $form = $crawler->selectButton('Register')->form();

        // fill form with valid credentials
        $form['registration_form[username]'] = ByteString::fromRandom(10)->toString();
        $form['registration_form[password][first]'] = 'testtest';
        $form['registration_form[password][second]'] = 'testtest';

        // submit the form
        $this->client->submit($form);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
