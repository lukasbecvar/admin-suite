<?php

namespace App\Tests\Event\Subscriber;

use Exception;
use Throwable;
use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Event\Subscriber\ExceptionEventSubscriber;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ExceptionEventSubscriberTest
 *
 * Test cases for the exception event subscriber
 *
 * @package App\Tests\Event\Subscriber
 */
class ExceptionEventSubscriberTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private ExceptionEventSubscriber $subscriber;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create exception event subscriber instance
        $this->subscriber = new ExceptionEventSubscriber($this->appUtilMock, $this->loggerMock, $this->errorManagerMock);
    }

    /**
     * Create an exception event
     *
     * @param Throwable $exception The exception
     *
     * @return ExceptionEvent The exception event
     */
    public function createExceptionEvent(Throwable $exception): ExceptionEvent
    {
        /** @var HttpKernelInterface $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        /** @var Request $requestMock */
        $requestMock = $this->createMock(Request::class);

        // return exception event
        return new ExceptionEvent($kernelMock, $requestMock, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    /**
     * Test untrusted host exception in non dev mode
     *
     * @return void
     */
    public function testUntrustedHostExceptionInNonDevMode(): void
    {
        // mock dev mode disabled
        $this->appUtilMock->method('isDevMode')->willReturn(false);

        // mock error view
        $this->errorManagerMock->method('getErrorView')->with('400')->willReturn('Error content');

        // create exception event
        $event = $this->createExceptionEvent(new Exception('Untrusted Host detected'));

        // create & call exception event subscriber
        $this->subscriber->onKernelException($event);

        // get response
        $response = $event->getResponse();

        // assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Error content', $response->getContent());
        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    /**
     * Test untrusted host exception in dev mode
     *
     * @return void
     */
    public function testUntrustedHostExceptionInDevMode(): void
    {
        // mock dev mode enabled
        $this->appUtilMock->method('isDevMode')->willReturn(true);

        // create exception event
        $event = $this->createExceptionEvent(new Exception('Untrusted Host detected'));

        // expect exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Untrusted Host detected');

        // create & call exception event subscriber
        $this->subscriber->onKernelException($event);
    }

    /**
     * Test http exception excluded from logging
     *
     * @return void
     */
    public function testHttpExceptionExcludedFromLogging(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => [404],
                    ],
                ],
            ],
        ]);

        // create exception event
        $event = $this->createExceptionEvent(new HttpException(Response::HTTP_NOT_FOUND, 'Not Found'));

        // expect logger not to be called
        $this->loggerMock->expects($this->never())->method('error');

        // create & call exception event subscriber
        $this->subscriber->onKernelException($event);
    }

    /**
     * Test http exception logged
     *
     * @return void
     */
    public function testHttpExceptionLogged(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => [404],
                    ],
                ],
            ],
        ]);

        // create exception event
        $event = $this->createExceptionEvent(
            new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal Server Error')
        );

        // expect logger to be called
        $this->loggerMock->expects($this->once())->method('error')->with('Internal Server Error');

        // create & call exception event subscriber
        $this->subscriber->onKernelException($event);
    }

    /**
     * Test non http exception logged
     *
     * @return void
     */
    public function testNonHttpExceptionLogged(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => [],
                    ],
                ],
            ],
        ]);

        // create exception event
        $event = $this->createExceptionEvent(new Exception('Generic Exception'));

        // expect logger to be called
        $this->loggerMock->expects($this->once())->method('error')->with('Generic Exception');

        // create & call exception event subscriber
        $this->subscriber->onKernelException($event);
    }
}
