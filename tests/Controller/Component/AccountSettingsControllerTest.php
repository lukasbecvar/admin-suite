<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AccountSettingsControllerTest
 *
 * Test cases for account settings component
 *
 * @package App\Tests\Controller\Component
 */
class AccountSettingsControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load account settings table
     *
     * @return void
     */
    public function testLoadAccountSettingsTable(): void
    {
        $this->client->request('GET', '/account/settings');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Account Settings")');
        $this->assertSelectorExists('div:contains("Username")');
        $this->assertSelectorExists('div:contains("Password")');
        $this->assertSelectorExists('div:contains("Profile Image")');
        $this->assertSelectorExists('a:contains("Change")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load profile picture change form
     *
     * @return void
     */
    public function testLoadProfilePictureChangeForm(): void
    {
        $this->client->request('GET', '/account/settings/change/picture');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change picture")');
        $this->assertSelectorExists('input[name="profile_pic_change_form[profile-pic]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit profile picture change form with empty image file
     *
     * @return void
     */
    public function testSubmitProfilePictureChangeFormWithEmptyImage(): void
    {
        $this->client->request('POST', '/account/settings/change/picture', [
            'profile_pic_change_form' => [
                'profile-pic' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change picture")');
        $this->assertSelectorExists('input[name="profile_pic_change_form[profile-pic]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Please add picture file.")', 'Please add picture file.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load username change form
     *
     * @return void
     */
    public function testLoadUsernameChangeForm(): void
    {
        $this->client->request('GET', '/account/settings/change/username');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit change username with empty username
     *
     * @return void
     */
    public function testSubmitChangeUsernameFormWithEmptyUsernameInput(): void
    {
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit change username form with username length is low
     *
     * @return void
     */
    public function testSubmitChangeUsernameFormWithLowLength(): void
    {
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => '1'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Your username should be at least 3 characters")', 'Your username should be at least 3 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit change username form with username length is higher
     *
     * @return void
     */
    public function testSubmitChangeFormWithUsernameHigherLength(): void
    {
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => 'asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Your username cannot be longer than 155 characters")', 'Your username cannot be longer than 155 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit username change form with success response
     *
     * @return void
     */
    public function testSubmitChangeUsernameFormWithSuccessResponse(): void
    {
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => ByteString::fromRandom(10)->toByteString()
            ]
        ]);

        // assert response
        $this->assertResponseRedirects('/account/settings');
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test load pasword change form
     *
     * @return void
     */
    public function testLoadPasswordChangeForm(): void
    {
        $this->client->request('GET', '/account/settings/change/password');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change Password")');
        $this->assertSelectorExists('input[name="password_change_form[password][first]"]');
        $this->assertSelectorExists('input[name="password_change_form[password][second]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit password change form with empty password
     *
     * @return void
     */
    public function testSubmitPasswordChangeFormWithEmptyPasswordInput(): void
    {
        $this->client->request('POST', '/account/settings/change/password', [
            'password_change_form' => [
                'password' => [
                    'first' => '',
                    'second' => ''
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change Password")');
        $this->assertSelectorExists('input[name="password_change_form[password][first]"]');
        $this->assertSelectorExists('input[name="password_change_form[password][second]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit password change form with low length password
     *
     * @return void
     */
    public function testSubmitPasswordChangeFormWithLowLengthPassword(): void
    {
        $this->client->request('POST', '/account/settings/change/password', [
            'password_change_form' => [
                'password' => [
                    'first' => 'a',
                    'second' => 'a'
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change Password")');
        $this->assertSelectorExists('input[name="password_change_form[password][first]"]');
        $this->assertSelectorExists('input[name="password_change_form[password][second]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit password change form with higher length password
     *
     * @return void
     */
    public function testSubmitPasswordChangeFormWithHigherLengthPassword(): void
    {
        $this->client->request('POST', '/account/settings/change/password', [
            'password_change_form' => [
                'password' => [
                    'first' => 'asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf',
                    'second' => 'asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf'
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change Password")');
        $this->assertSelectorExists('input[name="password_change_form[password][first]"]');
        $this->assertSelectorExists('input[name="password_change_form[password][second]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Your password cannot be longer than 155 characters")', 'Your password cannot be longer than 155 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit password change form with not matched passwords
     *
     * @return void
     */
    public function testSubmitPasswordChangeFormWithNotMatchedPasswords(): void
    {
        $this->client->request('POST', '/account/settings/change/password', [
            'password_change_form' => [
                'password' => [
                    'first' => 'testtesttest',
                    'second' => 'testtesttestff'
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change Password")');
        $this->assertSelectorExists('input[name="password_change_form[password][first]"]');
        $this->assertSelectorExists('input[name="password_change_form[password][second]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("The values do not match.")', 'The values do not match.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit password change form with success response
     *
     * @return void
     */
    public function testSubmitPasswordChangeFormWithSuccessResponse(): void
    {
        $this->client->request('POST', '/account/settings/change/password', [
            'password_change_form' => [
                'password' => [
                    'first' => 'testtesttest',
                    'second' => 'testtesttest'
                ]
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
