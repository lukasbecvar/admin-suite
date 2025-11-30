<?php

namespace App\Tests\Middleware;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ConfigManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Middleware\AuthenticatedCheckMiddleware;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddlewareTest
 *
 * Test cases for authenticated check middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(AuthenticatedCheckMiddleware::class)]
class AuthenticatedCheckMiddlewareTest extends TestCase
{
    private LogManager & MockObject $logManagerMock;
    private AuthenticatedCheckMiddleware $middleware;
    private AuthManager & MockObject $authManagerMock;
    private ConfigManager & MockObject $configManagerMock;
    private UrlGeneratorInterface & MockObject $urlGeneratorMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->configManagerMock = $this->createMock(ConfigManager::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        // create middleware instance
        $this->middleware = new AuthenticatedCheckMiddleware(
            $this->logManagerMock,
            $this->authManagerMock,
            $this->configManagerMock,
            $this->urlGeneratorMock
        );
    }

    /**
     * Create request event
     *
     * @param string $pathInfo
     *
     * @return RequestEvent
     */
    private function createRequestEvent(string $pathInfo): RequestEvent
    {
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($pathInfo, 'GET');
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Test request when user is logged in
     *
     * @return void
     */
    public function testRequestWhenUserIsLoggedIn(): void
    {
        // create testing request event
        $event = $this->createRequestEvent('/api/system/resources');

        // mock config manager to return no exclusions
        $this->configManagerMock->method('readConfig')->willReturn('[]');

        // simulate user is logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertNull($event->getResponse());
    }

    /**
     * Test request to admin page when user is not logged in
     *
     * @return void
     */
    public function testRequestToAdminPageWhenUserIsNotLoggedIn(): void
    {
        // create testing request event
        $event = $this->createRequestEvent('/dashboard');

        // mock config manager to return no exclusions
        $this->configManagerMock->method('readConfig')->willReturn('[]');

        // simulate user is not logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // expect call url generator
        $this->urlGeneratorMock->expects($this->once())
            ->method('generate')->with('app_auth_login')->willReturn('/login');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * Test request when API-KEY header is provided with valid token
     *
     * @return void
     */
    public function testRequestWithApiKeyHeader(): void
    {
        // mock config manager to return no exclusions
        $this->configManagerMock->method('readConfig')->willReturn('[]');

        // simulate user is logged in
        $event = $this->createRequestEvent('/api/system/resources');
        $event->getRequest()->headers->set('API-KEY', 'valid-token');
        $this->assertTrue($event->getRequest()->headers->has('API-KEY'));

        // mock auth manager
        $this->authManagerMock->expects($this->once())
            ->method('authenticateWithApiKey')->with('valid-token')->willReturn(true);

        // expect login check to be skipped (user for regular route request)
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response (null = no redirect to login page)
        $this->assertNull($event->getResponse());
    }

    /**
     * Test request when pages are excluded from authentication check
     *
     * @return void
     */
    public function testRequestsForExcludedPages(): void
    {
        // mock config manager to return exclusions
        $exclusions = [
            'exact_paths' => ['/login', '/'],
            'path_prefixes' => ['/error', '/api/public'],
            'path_patterns' => ['^/(_profiler|_wdt)']
        ];
        $this->configManagerMock->method('readConfig')->with('security-exclusions.json')->willReturn(json_encode($exclusions));

        // test paths
        $testPaths = [
            'exact' => '/login',
            'exact_root' => '/',
            'prefix' => '/error/404',
            'prefix_api' => '/api/public/data',
            'pattern' => '/_profiler/12345'
        ];

        // expect auth manager not called for any of these paths
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        foreach ($testPaths as $key => $path) {
            $event = $this->createRequestEvent($path);
            $this->middleware->onKernelRequest($event);
            $this->assertNull($event->getResponse(), "Failed asserting for excluded path type: $key, path: $path");
        }
    }

    /**
     * Test request when exclusion config is missing
     *
     * @return void
     */
    public function testRequestWhenExclusionConfigIsMissing(): void
    {
        // mock config manager to return null
        $this->configManagerMock->method('readConfig')->with('security-exclusions.json')->willReturn(null);

        // expect log manager to log warning
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'suite-config',
            'security-exclusions.json not found, auth check running on all paths',
            LogManager::LEVEL_WARNING
        );

        // expect auth check to run because config is missing
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // call tested middleware
        $event = $this->createRequestEvent('/some-path');
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when exclusion config is empty
     *
     * @return void
     */
    public function testRequestWhenExclusionConfigIsInvalid(): void
    {
        // mock config manager to return invalid config
        $this->configManagerMock->method('readConfig')->with('security-exclusions.json')
            ->willReturn('{ "invalid_json": ...');

        // expect log manager to log error
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'suite-config-error',
            $this->stringContains('Error parsing security-exclusions.json'),
            LogManager::LEVEL_CRITICAL
        );

        // expect auth check to run because config is invalid (fail-secure)
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // call tested middleware
        $event = $this->createRequestEvent('/some-path');
        $this->middleware->onKernelRequest($event);
    }
}
