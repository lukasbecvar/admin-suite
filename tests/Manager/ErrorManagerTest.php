<?php

namespace App\Tests\Manager;

use Twig\Environment;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManagerTest
 *
 * Test cases for error manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(ErrorManager::class)]
class ErrorManagerTest extends TestCase
{
    private ErrorManager $errorManager;
    private Environment & MockObject $twigMock;
    private LoggerInterface & MockObject $loggerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->twigMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager instance
        $this->errorManager = new ErrorManager($this->twigMock, $this->loggerMock);
    }

    /**
     * Test handle error exception
     *
     * @return void
     */
    public function testHandleErrorException(): void
    {
        // expect the HttpException
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call tested method
        $this->errorManager->handleError('Page not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * Test get error view
     *
     * @return void
     */
    public function testGetErrorView(): void
    {
        // expect the error view
        $this->twigMock->expects($this->once())->method('render')
            ->with('error/error-404.twig')->willReturn('error view');

        // call tested method
        $result = $this->errorManager->getErrorView(Response::HTTP_NOT_FOUND);

        // assert result
        $this->assertEquals('error view', $result);
    }

    /**
     * Test log error to exception log
     *
     * @return void
     */
    public function testLogErrorToExceptionLog(): void
    {
        // expect the logger error
        $this->loggerMock->expects($this->once())->method('error')->with(
            'error message',
            [
                'code' => Response::HTTP_NOT_FOUND
            ]
        );

        // call tested method
        $this->errorManager->logError('error message', Response::HTTP_NOT_FOUND);
    }
}
