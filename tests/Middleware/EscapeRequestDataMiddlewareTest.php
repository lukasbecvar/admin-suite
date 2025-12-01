<?php

namespace App\Tests\Middleware;

use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\ConfigManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Middleware\EscapeRequestDataMiddleware;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class EscapeRequestDataMiddlewareTest
 *
 * Test cases for escape request data middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(EscapeRequestDataMiddleware::class)]
class EscapeRequestDataMiddlewareTest extends TestCase
{
    private LogManager & MockObject $logManager;
    private SecurityUtil & MockObject $securityUtil;
    private ConfigManager & MockObject $configManager;
    private EscapeRequestDataMiddleware $middleware;

    protected function setUp(): void
    {
        // mock security util logic
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->securityUtil->method('escapeString')
            ->willReturnCallback(fn (string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5));

        // mock config manager logic
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->method('readConfig')->with('escape-request-exclusions.json')->willReturn(json_encode([
            'routes' => [
                'app_file_system_view'
            ]
        ], JSON_THROW_ON_ERROR));

        // mock log manager logic
        $this->logManager = $this->createMock(LogManager::class);

        // create middleware instance
        $this->middleware = new EscapeRequestDataMiddleware(
            $this->logManager,
            $this->securityUtil,
            $this->configManager
        );
    }

    /**
     * Test request data is escaped when route is NOT excluded
     *
     * @return void
     */
    public function testEscapeRequestData(): void
    {
        // create request with POST data
        $requestData = [
            'name' => '<script>alert("XSS Attack!");</script>',
            'email' => 'user@example.com',
            'message' => '<p>Hello, World!</p>'
        ];

        $request = new Request([], $requestData);
        $request->attributes->set('_route', 'some_normal_route');

        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // call middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertSame('user@example.com', $request->request->get('email'));
        $this->assertSame('&lt;p&gt;Hello, World!&lt;/p&gt;', $request->request->get('message'));
        $this->assertSame('&lt;script&gt;alert(&quot;XSS Attack!&quot;);&lt;/script&gt;', $request->request->get('name'));
    }

    /**
     * Test request data is NOT escaped when route is excluded
     *
     * @return void
     */
    public function testExcludedRouteDoesNotEscapeData(): void
    {
        // create request with POST data
        $requestData = [
            'name' => '<script>alert("XSS Attack!");</script>',
            'email' => 'user@example.com',
            'message' => '<p>Hello, World!</p>'
        ];

        // set excluded route name
        $request = new Request([], $requestData);
        $request->attributes->set('_route', 'app_file_system_view');

        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // call middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertSame('<script>alert("XSS Attack!");</script>', $request->request->get('name'));
        $this->assertSame('user@example.com', $request->request->get('email'));
        $this->assertSame('<p>Hello, World!</p>', $request->request->get('message'));
    }
}
