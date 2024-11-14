<?php

namespace App\Tests\Controller\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NonAuthRedirectTest
 *
 * Test reidrect non-authenticated users to login page for admin page routes
 *
 * @package App\Tests\Controller\Auth
 */
class NonAuthRedirectTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Auth required routes list
     *
     * @return array<array<string>>
     */
    private const ROUTES = [
        'api' => [
            '/api/system/terminal',
            '/api/notifications/enabled',
            '/api/notifications/subscribe',
            '/api/notifications/public-key'
        ],
        'anti_log' => [
            '/13378/antilog'
        ],
        'admin_dashboard' => [
            '/admin',
            '/dashboard'
        ],
        'user_manager' => [
            '/manager/users',
            '/manager/users/ban',
            '/manager/users/delete',
            '/manager/users/register',
            '/manager/users/role/update'
        ],
        'account_settings' => [
            '/account/settings',
            '/manager/users/profile',
            '/account/settings/change/picture',
            '/account/settings/change/username',
            '/account/settings/change/password'
        ],
        'logs_manager' => [
            '/manager/logs',
            '/manager/logs/system',
            '/manager/logs/set/readed',
            '/manager/logs/exception/files'
        ],
        'diagnostic' => [
            '/diagnostic'
        ],
        'action_runner' => [
            '/service/action/runner'
        ],
        'monitoring_manager' => [
            '/manager/monitoring',
            '/manager/monitoring/config'
        ],
        'todo_manager' => [
            '/manager/todo',
            '/manager/todo/edit',
            '/manager/todo/close',
            '/manager/todo/delete'
        ],
        'database_manager' => [
            '/manager/database',
            '/manager/database/add',
            '/manager/database/edit',
            '/manager/database/dump',
            '/manager/database/table',
            '/manager/database/delete',
            '/manager/database/console',
            '/manager/database/truncate'
        ],
        'file_browser' => [
            '/filesystem',
            '/filesystem/view',
            '/filesystem/get/resource'
        ],
        'terminal' => [
            '/terminal',
            '/api/system/terminal'
        ],
        'metrics' => [
            '/metrics/dashboard'
        ]
    ];

    /**
     * Admin routes list provider
     *
     * @return array<array<string>>
     */
    protected function provideAdminUrls(): array
    {
        $urls = [];
        foreach (self::ROUTES as $category => $routes) {
            foreach ($routes as $route) {
                $urls[] = [$route];
            }
        }
        return $urls;
    }

    /**
     * Test non-authenticated requests redirect
     *
     * @dataProvider provideAdminUrls
     *
     * @param string $url The admin route URL
     *
     * @return void
     */
    public function testNonAuthRedirect(string $url): void
    {
        $this->client->request('GET', $url);

        // assert response
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
