<?php

namespace App\Tests\Middleware;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\EscapeRequestDataMiddleware;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class EscapeRequestDataMiddlewareTest
 *
 * Test cases for escape request data middleware
 *
 * @package App\Tests\Middleware
 */
class EscapeRequestDataMiddlewareTest extends TestCase
{
    private RequestStack $requestStack;
    private SecurityUtil & MockObject $securityUtil;
    private EscapeRequestDataMiddleware $middleware;

    protected function setUp(): void
    {
        // mock dependencies
        $this->requestStack = new RequestStack();
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $urlGeneratorInterface = $this->createMock(UrlGeneratorInterface::class);

        // mock URL generator to return specific paths for excluded routes
        $urlGeneratorInterface->method('generate')
            ->willReturnCallback(function ($route) {
                if ($route === 'app_manager_database_console') {
                    return '/manager/database/console';
                } elseif ($route === 'app_file_system_save') {
                    return '/filesystem/save';
                }
                return '/other/route';
            });

        $this->securityUtil->method('escapeString')->willReturnCallback(function (string $value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
        });

        // create middleware instance
        $this->middleware = new EscapeRequestDataMiddleware($this->securityUtil, $urlGeneratorInterface);
    }

    /**
     * Test escape request data for normal routes
     *
     * @return void
     */
    public function testEscapeRequestData(): void
    {
        // create request with unescaped data
        $requestData = [
            'name' => '<script>alert("XSS Attack!");</script>',
            'email' => 'user@example.com',
            'message' => '<p>Hello, World!</p>'
        ];

        // create request and push it to RequestStack
        $request = new Request([], $requestData);
        $request->server->set('REQUEST_URI', '/normal/route');
        $this->requestStack->push($request);

        // create a request event
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        /** @var Request $request */
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertEquals('user@example.com', $request->get('email'));
        $this->assertEquals('&lt;p&gt;Hello, World!&lt;/p&gt;', $request->get('message'));
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS Attack!&quot;);&lt;/script&gt;', $request->get('name'));
    }

    /**
     * Test that file system save route is excluded from escaping
     *
     * @return void
     */
    public function testFileSystemSaveRouteIsExcluded(): void
    {
        // create request with unescaped data
        $requestData = [
            'path' => '/path/to/file.php',
            'content' => '<?php echo "<p>Hello, World!</p>"; ?>'
        ];

        // create request and push it to RequestStack
        $request = new Request([], $requestData);
        $request->server->set('REQUEST_URI', '/filesystem/save');
        $this->requestStack->push($request);

        // create a request event
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);

        /** @var Request $request */
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert that content is not escaped
        $this->assertEquals('/path/to/file.php', $request->get('path'));
        $this->assertEquals('<?php echo "<p>Hello, World!</p>"; ?>', $request->get('content'));
    }
}
