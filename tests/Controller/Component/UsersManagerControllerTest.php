<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use App\Exception\AppErrorException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class UsersManagerControllerTest
 *
 * Test the users-manager component
 *
 * @package App\Tests\Controller\Component
 */
class UsersManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        // @phpstan-ignore-next-line
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // simulate login
        $this->simulateLogin($this->client);
    }

    /**
     * Test the users-manager load
     *
     * @return void
     */
    public function testUserManagerLoad(): void
    {
        $this->client->request('GET', '/manager/users');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
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
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the users-manager profile view load
     *
     * @return void
     */
    public function testProfileViewLoad(): void
    {
        $this->client->request('GET', '/manager/users/profile', [
            'id' => $this->getRandomUserId($this->entityManager)
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('img[alt="User Profile Picture"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the users-manager register load
     *
     * @return void
     */
    public function testUserManagerRegisterLoad(): void
    {
        $this->client->request('GET', '/manager/users/register');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('form[name="registration_form"]');
        $this->assertSelectorExists('input[name="registration_form[username]"]');
        $this->assertSelectorExists('input[name="registration_form[password][first]"]');
        $this->assertSelectorExists('input[name="registration_form[password][second]"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the users-manager register submit with empty inputs
     *
     * @return void
     */
    public function testUserManagerRegisterSubmitEmptyInputs(): void
    {
        // make request
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
     * Test the users-manager register submit with invalid inputs length
     *
     * @return void
     */
    public function testUserManagerRegisterSubmitInvalidLength(): void
    {
        // make request
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
     * Test the users-manager register submit with not matching passwords
     *
     * @return void
     */
    public function testUserManagerRegisterSubmitNotMatchingPasswords(): void
    {
        // make request
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
     * Test the users-manager register submit valid
     *
     * @return void
     */
    public function testUserManagerRegisterSubmitValid(): void
    {
        // make request
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
     * Test the users-manager update submit with empty id
     *
     * @return void
     */
    public function testUserManagerUpdateUserRoleEmptyId(): void
    {
        // Set the expected exception
        $this->expectException(AppErrorException::class);
        $this->expectExceptionMessage('invalid request user "id" parameter not found in query');

        // make request
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => '',
            'role' => ''
        ]);
    }

    /**
     * Test the users-manager update submit with empty role
     *
     * @return void
     */
    public function testUserManagerUpdateUserRoleEmptyRole(): void
    {
        // Set the expected exception
        $this->expectException(AppErrorException::class);
        $this->expectExceptionMessage('invalid request user "role" parameter not found in query');

        // make request
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => 1,
            'role' => ''
        ]);
    }

    /**
     * Test the users-manager update submit with invalid id
     *
     * @return void
     */
    public function testUserManagerUpdateUserRoleInvalidId(): void
    {
        // Set the expected exception
        $this->expectException(AppErrorException::class);
        $this->expectExceptionMessage('invalid request user "id" parameter not found in database');

        // make request
        $this->client->request('POST', '/manager/users/role/update', [
            'id' => 13383838383,
            'role' => 'admin'
        ]);
    }

    /**
     * Test the users-manager delete submit with invalid id
     *
     * @return void
     */
    public function testUserManagerUserDeleteEmptyId(): void
    {
        // Set the expected exception
        $this->expectException(AppErrorException::class);
        $this->expectExceptionMessage('invalid request user "id" parameter not found in query');

        // make request
        $this->client->request('GET', '/manager/users/delete', [
            'id' => ''
        ]);
    }

    /**
     * Test the users-manager delete submit with invalid id
     *
     * @return void
     */
    public function testUserManagerUserDeleteInvalidId(): void
    {
        // Set the expected exception
        $this->expectException(AppErrorException::class);
        $this->expectExceptionMessage('invalid request user "id" parameter not found in database');

        // make request
        $this->client->request('GET', '/manager/users/delete', [
            'id' => 1323232323232
        ]);
    }
}
