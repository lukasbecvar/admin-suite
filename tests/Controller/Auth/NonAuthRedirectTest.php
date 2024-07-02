<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NonAuthRedirectTest
 *
 * Non-auth redirect authenticator test
 * Test all admin routes in the default state when the user is not logged in
 *
 * @package App\Tests\Controller\Auth
 */
class NonAuthRedirectTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        parent::setUp();
    }

    /**
     * Auth required routes list
     *
     * @return array<array<string>>
     */
    public function provideAdminUrls(): array
    {
        return [
            // admin dashboard routes
            ['/admin'],
            ['/dashboard'],

            // anti-log route
            ['/13378/antilog'],

            // users manager system routes
            ['/manager/users'],
            ['/manager/users/ban'],
            ['/manager/users/delete'],
            ['/manager/users/register'],
            ['/manager/users/role/update'],

            // account settings routes
            ['/account/settings'],
            ['/manager/users/profile'],
            ['/account/settings/change/picture'],
            ['/account/settings/change/username'],
            ['/account/settings/change/password'],

            // logs manager
            ['/manager/logs'],
            ['/manager/logs/system'],
            ['/manager/logs/set/readed']
        ];
    }

    /**
     * Test non-authenticated admin redirect
     *
     * @dataProvider provideAdminUrls
     *
     * @param string $url The admin route URL
     *
     * @return void
     */
    public function testNonAuthAdminRedirect(string $url): void
    {
        $this->client->request('GET', $url);

        // assert
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
    }
}
