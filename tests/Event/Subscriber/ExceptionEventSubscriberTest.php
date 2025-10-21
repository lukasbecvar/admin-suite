<?php

namespace App\Tests\Event\Subscriber;

use Exception;
use Throwable;
use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Controller\ErrorController;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use App\Event\Subscriber\ExceptionEventSubscriber;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ExceptionEventSubscriberTest
 *
 * Test cases for exception event subscriber
 *
 * @package App\Tests\Event\Subscriber
 */
#[CoversClass(ExceptionEventSubscriber::class)]
class ExceptionEventSubscriberTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private ExceptionEventSubscriber $subscriber;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorController & MockObject $errorController;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorController = $this->createMock(ErrorController::class);

        // create exception event subscriber instance
        $this->subscriber = new ExceptionEventSubscriber($this->appUtilMock, $this->loggerMock, $this->errorController);
    }

    /**
     * Create exception event
     *
     * @param Throwable $exception The exception
     *
     * @return ExceptionEvent The exception event
     */
    private function createExceptionEvent(Throwable $exception): ExceptionEvent
    {
        /** @var HttpKernelInterface&MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);

        /** @var Request&MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);

        // return exception event
        return new ExceptionEvent($kernelMock, $requestMock, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    /**
     * Test handle http exception excluded from logging
     *
     * @return void
     */
    public function testHandleHttpExceptionExcludedFromLogging(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => [404]
                    ]
                ]
            ]
        ]);

        // create exception event
        $event = $this->createExceptionEvent(new HttpException(Response::HTTP_NOT_FOUND, 'Not Found'));

        // expect logger not to be called
        $this->loggerMock->expects($this->never())->method('error');

        // call exception event subscriber
        $this->subscriber->onKernelException($event);
    }

    /**
     * Test handle http exception logged to exception log
     *
     * @return void
     */
    public function testHandleHttpExceptionLoggedToExceptionLog(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => [404]
                    ]
                ]
            ]
        ]);

        // create exception event
        $event = $this->createExceptionEvent(
            new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal Server Error')
        );

        // expect logger to be called
        $this->loggerMock->expects($this->once())->method('error')->with('Internal Server Error');

        // call exception event subscriber
        $this->subscriber->onKernelException($event);
    }

    /**
     * Test handle non http exception
     *
     * @return void
     */
    public function testHandleNonHttpException(): void
    {
        // mock excluded http codes
        $this->appUtilMock->method('getYamlConfig')->with('packages/monolog.yaml')->willReturn([
            'monolog' => [
                'handlers' => [
                    'filtered' => [
                        'excluded_http_codes' => []
                    ]
                ]
            ]
        ]);

        // create exception event
        $event = $this->createExceptionEvent(new Exception('Generic Exception'));

        // expect logger to be called
        $this->loggerMock->expects($this->once())->method('error')->with('Generic Exception');

        // call exception event subscriber
        $this->subscriber->onKernelException($event);
    }
}
