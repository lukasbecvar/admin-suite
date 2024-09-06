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
 * Test cases for EscapeRequestDataMiddleware class
 *
 * @package App\Tests\Middleware
 */
class EscapeRequestDataMiddlewareTest extends TestCase
{
    /** @var RequestStack */
    private RequestStack $requestStack;

    /** @var SecurityUtil&MockObject */
    private SecurityUtil|MockObject $securityUtil;

    /** @var EscapeRequestDataMiddleware */
    private EscapeRequestDataMiddleware $middleware;

    protected function setUp(): void
    {
        // mock SecurityUtil
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->securityUtil->method('escapeString')->willReturnCallback(function ($value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
        });

        // mock RequestStack
        $this->requestStack = new RequestStack();

        /** @var MockObject&UrlGeneratorInterface $urlGeneratorInterface */
        $urlGeneratorInterface = $this->createMock(UrlGeneratorInterface::class);

        // create middleware instance
        $this->middleware = new EscapeRequestDataMiddleware($this->securityUtil, $urlGeneratorInterface);
    }

    /**
     * Test the security escaping of request data
     *
     * @return void
     */
    public function testEscapeRequestData(): void
    {
        // create a request with unescaped data
        $requestData = [
            'name' => '<script>alert("XSS Attack!");</script>',
            'email' => 'user@example.com',
            'message' => '<p>Hello, World!</p>'
        ];

        // create a request and push it to RequestStack
        $request = new Request([], $requestData);
        $this->requestStack->push($request);

        // create a request event
        /** @var MockObject&HttpKernelInterface $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        /** @var MockObject&Request $request */
        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        // execute the middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS Attack!&quot;);&lt;/script&gt;', $request->get('name'));
        $this->assertEquals('user@example.com', $request->get('email'));
        $this->assertEquals('&lt;p&gt;Hello, World!&lt;/p&gt;', $request->get('message'));
    }
}
