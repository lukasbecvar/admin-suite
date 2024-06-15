<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class AccountSettingsControllerTest
 *
 * Test the account-settings controller
 *
 * @package App\Tests\Controller\Component
 */
class AccountSettingsControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Test the account-settings load
     *
     * @return void
     */
    public function testLoadAccountSettingsTable(): void
    {
        $this->client->request('GET', '/account/settings');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Account Settings")');
        $this->assertSelectorExists('td:contains("Username")');
        $this->assertSelectorExists('td:contains("Password")');
        $this->assertSelectorExists('td:contains("Profile Image")');
        $this->assertSelectorExists('a:contains("Change")');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the username change form load
     *
     * @return void
     */
    public function testLoadAccountSettingsUsernameChangeForm(): void
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
     * Test the username change with empty username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameWithEmptyUsername(): void
    {
        // make request
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the username change with low length username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameLowLength(): void
    {
        // make request
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => '1'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('h2:contains("Change username")');
        $this->assertSelectorExists('input[name="username_change_form[username]"]');
        $this->assertSelectorExists('button:contains("Change")');
        $this->assertSelectorTextContains('li:contains("Your username should be at least 3 characters")', 'Your username should be at least 3 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the username change with higher length username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeUsernameHigherLength(): void
    {
        // make request
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
     * Test the username change with valid username
     *
     * @return void
     */
    public function testSubmitAccountSettingsChangeValid(): void
    {
        // make request
        $this->client->request('POST', '/account/settings/change/username', [
            'username_change_form' => [
                'username' => ByteString::fromRandom(10)->toByteString()
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test the password change form load
     *
     * @return void
     */
    public function testLoadAccountSettingsProfilePictureChangeForm(): void
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
     * Test the profile picture change with empty image
     *
     * @return void
     */
    public function testSubmitAccountSettingsProfilePictureChangeFormWithEmptyImage(): void
    {
        // make request
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
     * Test the password change form load
     *
     * @return void
     */
    public function testLoadAccountSettingsPasswordChangeForm(): void
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
     * Test the password change with empty inputs
     *
     * @return void
     */
    public function testSubmitAccountSettingsPasswordChangeFormWithEmptyInputs(): void
    {
        // make request
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
     * Test the password change with low length inputs
     *
     * @return void
     */
    public function testSubmitAccountSettingsPasswordChangeFormWithLowLengthInputs(): void
    {
        // make request
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
     * Test the password change with higher length inputs
     *
     * @return void
     */
    public function testSubmitAccountSettingsPasswordChangeFormWithHigherLengthInputs(): void
    {
        // make request
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
     * Test the password change with not matched passwords
     *
     * @return void
     */
    public function testSubmitAccountSettingsPasswordChangeFormWithNotMatchedPasswords(): void
    {
        // make request
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
     * Test the password change with valid inputs
     *
     * @return void
     */
    public function testSubmitAccountSettingsPasswordChangeFormValid(): void
    {
        // make request
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
