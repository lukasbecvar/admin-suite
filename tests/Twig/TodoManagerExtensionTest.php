<?php

namespace App\Tests\Twig;

use App\Manager\TodoManager;
use PHPUnit\Framework\TestCase;
use App\Twig\TodoManagerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TodoManagerExtensionTest
 *
 * Test cases for todo manager twig extension
 *
 * @package App\Tests\Twig
 */
#[CoversClass(TodoManagerExtension::class)]
class TodoManagerExtensionTest extends TestCase
{
    private TodoManager & MockObject $todoManager;
    private TodoManagerExtension $todoManagerExtension;

    protected function setUp(): void
    {
        $this->todoManager = $this->getMockBuilder(TodoManager::class)->disableOriginalConstructor()->getMock();
        $this->todoManagerExtension = new TodoManagerExtension($this->todoManager);
    }

    /**
     * Test get functions
     *
     * @return void
     */
    public function testGetFunctions(): void
    {
        // call tested method
        $functions = $this->todoManagerExtension->getFunctions();

        // assert result
        $this->assertCount(1, $functions);

        // check getTodosCount function
        $this->assertEquals('getTodosCount', $functions[0]->getName());
        $this->assertEquals([$this->todoManager, 'getTodosCount'], $functions[0]->getCallable());
    }

    /**
     * Test getTodosCount function
     *
     * @return void
     */
    public function testGetTodosCountFunction(): void
    {
        // mock TodoManager getTodosCount method
        $this->todoManager->method('getTodosCount')->willReturn(5);

        // get functions
        $functions = $this->todoManagerExtension->getFunctions();

        // find getTodosCount function
        $getTodosCountFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'getTodosCount') {
                $getTodosCountFunction = $function;
                break;
            }
        }

        // assert function exists
        $this->assertNotNull($getTodosCountFunction);

        // get callable
        $callable = $getTodosCountFunction->getCallable();
        $this->assertIsCallable($callable);
    }
}
