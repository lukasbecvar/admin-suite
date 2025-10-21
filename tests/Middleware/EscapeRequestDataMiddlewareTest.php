<?php

namespace App\Tests\Middleware;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
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
#[CoversClass(EscapeRequestDataMiddleware::class)]
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
        $this->securityUtil->method('escapeString')->willReturnCallback(function (string $value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
        });

        // create middleware instance
        $this->middleware = new EscapeRequestDataMiddleware($this->securityUtil, $urlGeneratorInterface);
    }

    /**
     * Test escape request data
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
}
