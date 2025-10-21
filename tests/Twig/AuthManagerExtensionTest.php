<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Twig\AuthManagerExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class AuthManagerExtensionTest
 *
 * Test cases for auth manager twig extension
 *
 * @package App\Tests\Twig
 */
#[CoversClass(AuthManagerExtension::class)]
class AuthManagerExtensionTest extends TestCase
{
    private AuthManager & MockObject $authManager;
    private AuthManagerExtension $authManagerExtension;

    protected function setUp(): void
    {
        $this->authManager = $this->getMockBuilder(AuthManager::class)->disableOriginalConstructor()->getMock();
        $this->authManagerExtension = new AuthManagerExtension($this->authManager);
    }

    /**
     * Test get functions
     *
     * @return void
     */
    public function testGetFunctions(): void
    {
        // call tested method
        $functions = $this->authManagerExtension->getFunctions();

        // assert result
        $this->assertCount(2, $functions);

        // check isAdmin function
        $this->assertEquals('isAdmin', $functions[0]->getName());
        $this->assertEquals([$this->authManager, 'isLoggedInUserAdmin'], $functions[0]->getCallable());

        // check getUserData function
        $this->assertEquals('getUserData', $functions[1]->getName());
        $this->assertEquals([$this->authManager, 'getLoggedUserRepository'], $functions[1]->getCallable());
    }

    /**
     * Test isAdmin function
     *
     * @return void
     */
    public function testIsAdminFunction(): void
    {
        // mock AuthManager isLoggedInUserAdmin method
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(true);

        // get functions
        $functions = $this->authManagerExtension->getFunctions();

        // find isAdmin function
        $isAdminFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'isAdmin') {
                $isAdminFunction = $function;
                break;
            }
        }

        // assert function exists
        $this->assertNotNull($isAdminFunction);

        // get callable
        $callable = $isAdminFunction->getCallable();
        $this->assertIsCallable($callable);
    }

    /**
     * Test getUserData function
     *
     * @return void
     */
    public function testGetUserDataFunction(): void
    {
        // mock user data
        $mockUser = $this->createMock(User::class);

        // mock AuthManager getLoggedUserRepository method
        $this->authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // get functions
        $functions = $this->authManagerExtension->getFunctions();

        // find getUserData function
        $getUserDataFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'getUserData') {
                $getUserDataFunction = $function;
                break;
            }
        }

        // assert function exists
        $this->assertNotNull($getUserDataFunction);

        // get callable
        $callable = $getUserDataFunction->getCallable();
        $this->assertIsCallable($callable);
    }
}
