<?php

namespace App\Tests\Manager;

use Twig\Environment;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManagerTest
 *
 * Test cases for error manager
 *
 * @package App\Tests\Manager
 */
class ErrorManagerTest extends TestCase
{
    private ErrorManager $errorManager;
    private Environment & MockObject $twigMock;

    protected function setUp(): void
    {
        // create the twig mock
        $this->twigMock = $this->createMock(Environment::class);

        // create the error manager instance
        $this->errorManager = new ErrorManager($this->twigMock);
    }

    /**
     * Test handle error exception
     *
     * @return void
     */
    public function testHandleError(): void
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
            ->with('error/error-404.twig')
            ->willReturn('error view');

        // call tested method
        $result = $this->errorManager->getErrorView(Response::HTTP_NOT_FOUND);

        // assert result
        $this->assertEquals('error view', $result);
    }
}
