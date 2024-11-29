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
