<?php

namespace App\Tests\Manager;

use Twig\Environment;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
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
    /**
     * Test error handling
     *
     * @return void
     */
    public function testHandleError(): void
    {
        // set the error parameters
        $code = 404;
        $message = 'Page not found';

        // create the twig mock
        $twigMock = $this->createMock(Environment::class);

        // create the error manager
        $errorManager = new ErrorManager($twigMock);

        // expect the HttpException
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);

        // call handle the error
        $errorManager->handleError($message, $code);
    }
}
