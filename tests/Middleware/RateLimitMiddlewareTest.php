<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use App\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class RateLimitMiddlewareTest
 *
 * Test cases for rate limit middleware
 *
 * @package App\Tests\Middleware
 */
class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private RequestEvent & MockObject $requestEventMock;
    private UrlGeneratorInterface & MockObject $urlGeneratorMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->requestEventMock = $this->createMock(RequestEvent::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        // instantiate tested middleware
        $this->middleware = new RateLimitMiddleware(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->errorManagerMock,
            $this->urlGeneratorMock
        );

        $request = new Request();
        $this->requestEventMock->method('getRequest')->willReturn($request);
    }

    /**
     * Test request when rate limit is disabled
     *
     * @return void
     */
    public function testRequestWhenRateLimitDisabled(): void
    {
        // simulate rate limit disabled
        $this->appUtilMock->method('getEnvValue')->with('RATE_LIMIT_ENABLED')->willReturn('false');

        // expect cache manager to not be called
        $this->cacheUtilMock->expects($this->never())->method('getValue');

        // call tested middleware
        $this->middleware->onKernelRequest($this->requestEventMock);
    }

    /**
     * Test request when current value is null
     *
     * @return void
     */
    public function testRequestWhenCurrentValueIsNull(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return cacheItem->get() = null
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->method('get')->willReturn(null);
        $this->cacheUtilMock->method('getValue')->willReturn($cacheItemMock);

        // expect call save current value to cache
        $this->cacheUtilMock->expects($this->once())->method('setValue')->with($this->stringContains('rate_limit_'), '1', 60);

        // call tested middleware
        $this->middleware->onKernelRequest($this->requestEventMock);
    }

    /**
     * Test request when current value is less than limit
     *
     * @return void
     */
    public function testSubsequentRequestWithinLimit(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return cacheItem->get() = 50
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->method('get')->willReturn('50');
        $this->cacheUtilMock->method('getValue')->willReturn($cacheItemMock);

        // simulate update current rate limit to 51
        $this->cacheUtilMock->expects($this->once())->method('setValue')->with($this->stringContains('rate_limit_'), '51', 60);

        // call tested middleware
        $this->middleware->onKernelRequest($this->requestEventMock);
    }

    /**
     * Test request when current value is greater than limit
     *
     * @return void
     */
    public function testRequestExceedingLimit(): void
    {
        // simulate env configuration
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['RATE_LIMIT_ENABLED', 'true'],
            ['RATE_LIMIT_INTERVAL', '60'],
            ['RATE_LIMIT_LIMIT', '100']
        ]);

        // simulate cache manager to return cacheItem->get() = 100
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->method('get')->willReturn('100');
        $this->cacheUtilMock->method('getValue')->willReturn($cacheItemMock);

        // expect error manager to be called
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelRequest($this->requestEventMock);
    }
}
