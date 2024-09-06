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
 * Test the error manager
 *
 * @package App\Tests\Manager
 */
class ErrorManagerTest extends TestCase
{
    /** @var Environment&MockObject */
    private Environment|MockObject $twigMock;

    /** @var ErrorManager */
    private ErrorManager $errorManager;

    protected function setUp(): void
    {
        // create the twig mock
        $this->twigMock = $this->createMock(Environment::class);

        // create the error manager
        $this->errorManager = new ErrorManager($this->twigMock);
    }

    /**
     * Test error handling
     *
     * @return void
     */
    public function testHandleError(): void
    {
        // expect the HttpException
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call handle the error
        $this->errorManager->handleError('Page not found', Response::HTTP_NOT_FOUND);
    }
}
