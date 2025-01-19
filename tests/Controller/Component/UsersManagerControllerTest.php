<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class UsersManagerControllerTest
 *
 * Test cases for users manager component
 *
 * @package App\Tests\Controller\Component
 */
class UsersManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // simulate user authentication
        $this->client = static::createClient();

        // @phpstan-ignore-next-line
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test load users manager page
     *
     * @return void
     */
    public function testLoadUsersManagerPage(): void
    {
        $this->client->request('GET', '/manager/users');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="View un-filtered users"]');
        $this->assertSelectorExists('a[title="Add new user"]');
        $this->assertSelectorExists('select[name="filter"]');
        $this->assertSelectorExists('th:contains("#")');
        $this->assertSelectorExists('th:contains("Username")');
        $this->assertSelectorExists('th:contains("Role")');
        $this->assertSelectorExists('th:contains("IP Address")');
        $this->assertSelectorExists('th:contains("Browser")');
        $this->assertSelectorExists('th:contains("OS")');
        $this->assertSelectorExists('th:contains("Last Login")');
        $this->assertSelectorExists('th:contains("Status")');
        $this->assertSelectorExists('th:contains("Banned")');
        $this->assertSelectorExists('th:contains("Ban")');
        $this->assertSelectorExists('a[class="ban-button"]');
        $this->assertSelectorExists('a[class="delete-button"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load profile view page
     *
     * @return void
     */
    public function testLoadProfileViewPage(): void
    {
        $this->client->request('GET', '/manager/users/profile', [
            'id' => $this->getRandomUserId($this->entityManager)
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to users manager"]');
        $this->assertSelectorExists('img[alt="User Profile Picture"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load user register page
     *
     * @return void
     */
    public function testLoadUserRegisterPage(): void
    {
        $this->client->request('GET', '/manager/users/register');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to users manager"]');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit user register form with empty inputs
     *
     * @return void
     */
    public function testSubmitUserRegisterWithEmptyInputs(): void
    {
        $this->client->request('POST', '/manager/users/register', [
            'registration_form' => [
                'username' => '',
                'password' => [
                    'first' => '',
                    'second' => ''
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to users manager"]');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('li:contains("Please enter a username")', 'Please enter a username');
        $this->assertSelectorTextContains('li:contains("Please enter a password")', 'Please enter a password');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit user register form with invalid inputs length
     *
     * @return void
     */
    public function testSubmitUserRegisterWithInvalidLength(): void
    {
        $this->client->request('POST', '/manager/users/register', [
            'registration_form' => [
                'username' => 'a',
                'password' => [
                    'first' => 'a',
                    'second' => 'a'
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[title="Back to users manager"]');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('li:contains("Your username should be at least 3 characters")', 'Your username should be at least 3 characters');
        $this->assertSelectorTextContains('li:contains("Your password should be at least 8 characters")', 'Your password should be at least 8 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit user register form with not matching passwords
     *
     * @return void
     */
    public function testSubmitUserRegisterWithNotMatchingPasswords(): void
    {
        $this->client->request('POST', '/manager/users/register', [
            'registration_form' => [
                'username' => 'valid-testing-username',
                'password' => [
                    'first' => 'passwordookokok',
                    'second' => 'passwordookokok1'
                ]
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('li:contains("The values do not match.")', 'The values do not match.');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit user register form with success response
     *
     * @return void
     */
    public function testSubmitUserRegisterWithSuccessResponse(): void
    {
        $this->client->request('POST', '/manager/users/register', [
            'registration_form' => [
                'username' => ByteString::fromRandom(10)->toByteString(),
                'password' => [
                    'first' => 'testtest',
                    'second' => 'testtest'
                ]
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test update user role submit with empty id
     *
     * @return void
     */
    public function testUpdateUserRoleWithEmptyId(): void
    {
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => '',
            'role' => ''
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user role submit with empty role
     *
     * @return void
     */
    public function testUpdateUserRoleWithEmptyRole(): void
    {
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => 1,
            'role' => ''
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test update user role submit with invalid id
     *
     * @return void
     */
    public function testUpdateUserRoleWithInvalidId(): void
    {
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => 13383838383,
            'role' => 'admin'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test ban user submit with empty id
     *
     * @return void
     */
    public function testBanUserWithEmptyId(): void
    {
        $this->client->request('GET', '/manager/users/ban', [
            'id' => ''
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test ban user submit with empty status
     *
     * @return void
     */
    public function testBanUserWithEmpty(): void
    {
        $this->client->request('GET', '/manager/users/ban', [
            'id' => 1,
            'status' => ''
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test ban user submit with invalid status
     *
     * @return void
     */
    public function testBanUserWithInvalidStatus(): void
    {
        $this->client->request('GET', '/manager/users/ban', [
            'id' => 1,
            'status' => 'invalid'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test ban user submit with not existing user
     *
     * @return void
     */
    public function testBanUserWithUserNotExist(): void
    {
        $this->client->request('GET', '/manager/users/ban', [
            'id' => 13383838383,
            'status' => 'active'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test ban user submit with success response
     *
     * @return void
     */
    public function testBanUserWithSuccessResponse(): void
    {
        $this->client->request('GET', '/manager/users/ban', [
            'id' => 2,
            'status' => 'active'
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test delete user submit with empty id
     *
     * @return void
     */
    public function testUserDeleteWithEmptyId(): void
    {
        $this->client->request('GET', '/manager/users/delete', [
            'id' => ''
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test delete user submit with invalid id
     *
     * @return void
     */
    public function testUserDeleteWithInvalidId(): void
    {
        $this->client->request('GET', '/manager/users/delete', [
            'id' => 1323232323232
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
