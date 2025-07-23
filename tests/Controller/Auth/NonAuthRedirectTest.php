<?php

namespace App\Tests\Controller\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NonAuthRedirectTest
 *
 * Test for redirect non-authenticated users to login page for admin page routes
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
     * @return array<array<string,string>>
     */
    private const ROUTES = [
        'api' => [
            ['method' => 'POST', 'url' => '/api/system/terminal'],
            ['method' => 'GET', 'url' => '/api/system/resources'],
            ['method' => 'GET', 'url' => '/api/notifications/enabled'],
            ['method' => 'POST', 'url' => '/api/notifications/subscribe'],
            ['method' => 'GET', 'url' => '/api/notifications/public-key']
        ],
        'anti_log' => [
            ['method' => 'GET', 'url' => '/13378/antilog']
        ],
        'app_about' => [
            ['method' => 'GET', 'url' => '/about']
        ],
        'admin_dashboard' => [
            ['method' => 'GET', 'url' => '/dashboard']
        ],
        'user_manager' => [
            ['method' => 'GET', 'url' => '/manager/users'],
            ['method' => 'GET', 'url' => '/manager/users/ban'],
            ['method' => 'GET', 'url' => '/manager/users/delete'],
            ['method' => 'GET', 'url' => '/manager/users/register'],
            ['method' => 'POST', 'url' => '/manager/users/role/update'],
            ['method' => 'GET', 'url' => '/manager/users/token/regenerate']
        ],
        'account_settings' => [
            ['method' => 'GET', 'url' => '/account/settings'],
            ['method' => 'GET', 'url' => '/manager/users/profile'],
            ['method' => 'GET', 'url' => '/account/settings/change/picture'],
            ['method' => 'GET', 'url' => '/account/settings/change/username'],
            ['method' => 'GET', 'url' => '/account/settings/change/password']
        ],
        'logs_manager' => [
            ['method' => 'GET', 'url' => '/manager/logs'],
            ['method' => 'GET', 'url' => '/manager/logs/system'],
            ['method' => 'GET', 'url' => '/manager/logs/set/readed'],
            ['method' => 'GET', 'url' => '/manager/logs/exception/files']
        ],
        'diagnostic' => [
            ['method' => 'GET', 'url' => '/diagnostic']
        ],
        'action_runner' => [
            ['method' => 'GET', 'url' => '/service/action/runner']
        ],
        'monitoring_manager' => [
            ['method' => 'GET', 'url' => '/manager/monitoring'],
            ['method' => 'GET', 'url' => '/manager/monitoring/config'],
            ['method' => 'GET', 'url' => '/manager/monitoring/export/slahistory']
        ],
        'todo_manager' => [
            ['method' => 'GET', 'url' => '/manager/todo'],
            ['method' => 'GET', 'url' => '/manager/todo/edit'],
            ['method' => 'GET', 'url' => '/manager/todo/info'],
            ['method' => 'GET', 'url' => '/manager/todo/close'],
            ['method' => 'GET', 'url' => '/manager/todo/reopen'],
            ['method' => 'GET', 'url' => '/manager/todo/delete'],
            ['method' => 'POST', 'url' => '/manager/todo/update-positions']
        ],
        'database_manager' => [
            ['method' => 'GET', 'url' => '/manager/database'],
            ['method' => 'GET', 'url' => '/manager/database/add'],
            ['method' => 'GET', 'url' => '/manager/database/edit'],
            ['method' => 'GET', 'url' => '/manager/database/dump'],
            ['method' => 'GET', 'url' => '/manager/database/table'],
            ['method' => 'GET', 'url' => '/manager/database/delete'],
            ['method' => 'GET', 'url' => '/manager/database/console'],
            ['method' => 'GET', 'url' => '/manager/database/truncate']
        ],
        'file_browser' => [
            ['method' => 'GET', 'url' => '/filesystem'],
            ['method' => 'GET', 'url' => '/filesystem/view'],
            ['method' => 'GET', 'url' => '/filesystem/move'],
            ['method' => 'GET', 'url' => '/filesystem/edit'],
            ['method' => 'POST', 'url' => '/filesystem/save'],
            ['method' => 'GET', 'url' => '/filesystem/create'],
            ['method' => 'GET', 'url' => '/filesystem/rename'],
            ['method' => 'GET', 'url' => '/filesystem/upload'],
            ['method' => 'POST', 'url' => '/filesystem/delete'],
            ['method' => 'GET', 'url' => '/filesystem/download'],
            ['method' => 'POST', 'url' => '/filesystem/move/save'],
            ['method' => 'POST', 'url' => '/filesystem/rename/save'],
            ['method' => 'POST', 'url' => '/filesystem/create/save'],
            ['method' => 'GET', 'url' => '/filesystem/get/resource'],
            ['method' => 'POST', 'url' => '/filesystem/upload/save'],
            ['method' => 'POST', 'url' => '/filesystem/upload/chunk']
        ],
        'terminal' => [
            ['method' => 'GET', 'url' => '/terminal']
        ],
        'metrics' => [
            ['method' => 'GET', 'url' => '/metrics/delete'],
            ['method' => 'GET', 'url' => '/metrics/service'],
            ['method' => 'GET', 'url' => '/metrics/dashboard'],
            ['method' => 'GET', 'url' => '/metrics/service/all']
        ],
        'system_audit' => [
            ['method' => 'GET', 'url' => '/system/audit']
        ]
    ];

    /**
     * Admin routes list provider
     *
     * @return array<int,array<int,string>>
     */
    public static function provideAdminUrls(): array
    {
        $urls = [];
        foreach (self::ROUTES as $category => $routes) {
            foreach ($routes as $route) {
                $urls[] = [$route['method'], $route['url']];
            }
        }
        return $urls;
    }

    /**
     * Test requests to admin routes that require authentication
     *
     * @param string $method The HTTP method
     * @param string $url The admin route URL
     *
     * @return void
     */
    #[DataProvider('provideAdminUrls')]
    public function testNonAuthRedirect(string $method, string $url): void
    {
        $this->client->request($method, $url);

        // assert response
        $this->assertTrue($this->client->getResponse()->isRedirect('/login'));
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
