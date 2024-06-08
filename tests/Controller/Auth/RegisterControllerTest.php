<?php

namespace App\Tests\Controller\Auth;

use App\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegisterControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    private function setMockUserManager(): void
    {
        $mockUserManager = $this->createMock(UserManager::class);
        $mockUserManager->method('isUsersEmpty')->willReturn(true);
        $this->client->getContainer()->set(UserManager::class, $mockUserManager);
    }

    public function testPageLoad(): void
    {
        $this->setMockUserManager();
        $this->client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
    }

    public function testSubmitForm(): void
    {
        $this->setMockUserManager();
        $this->client->request('GET', '/register');

        $crawler = $this->client->submitForm('Register', [
            'registration_form[username]' => 'WFEWFEWFEWFWWEF',
            'registration_form[password][first]' => 'testtest',
            'registration_form[password][second]' => 'testtest',
        ]);

        $this->assertResponseRedirects('/login');
    }

    public function testSubmitFormWithInvalidLength(): void
    {
        $this->setMockUserManager();
        $this->client->request('GET', '/register');

        $crawler = $this->client->submitForm('Register', [
            'registration_form[username]' => 'a',
            'registration_form[password][first]' => 'a',
            'registration_form[password][second]' => 'a',
        ]);

        $this->assertSelectorTextContains('li:contains("Your username should be at least 3 characters")', 'Your username should be at least 3 characters');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
    }
}
