<?php

namespace App\Tests\Manager;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ServiceManagerTest
 *
 * Test for the ServiceManager functionality
 *
 * @package App\Tests\Manager
 */
class ServiceManagerTest extends TestCase
{
    /** @var ServiceManager */
    private ServiceManager $serviceManager;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManager;

    /** @var AuthManager|MockObject */
    private AuthManager|MockObject $authManager;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManager;

    protected function setUp(): void
    {
        $this->logManager = $this->createMock(LogManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        $this->serviceManager = new ServiceManager(
            $this->logManager,
            $this->authManager,
            $this->errorManager
        );
    }

    /**
     * Test getServicesList method
     *
     * @return void
     */
    public function testGetServices(): void
    {
        // assert the result
        $this->assertIsArray($this->serviceManager->getServicesList());
    }

    /**
     * Test runAction method
     *
     * @return void
     */
    public function testRunAction(): void
    {
        $this->authManager->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(true);

        $userMock = $this->getMockBuilder(\App\Entity\User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->method('getUsername')
            ->willReturn('testUser');

        $this->authManager->expects($this->once())
            ->method('getLoggedUserRepository')
            ->willReturn($userMock);

        $this->logManager->expects($this->once())
            ->method('log')
            ->with('action-runner', 'testUser start example_service', 1);

        $this->serviceManager = $this->getMockBuilder(ServiceManager::class)
            ->setConstructorArgs([$this->logManager, $this->authManager, $this->errorManager])
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $this->serviceManager->runAction('example_service', 'start');
    }

    /**
     * Test isServiceRunning method
     *
     * @return void
     */
    public function testIsServiceRunning(): void
    {
        $this->assertIsBool($this->serviceManager->isServiceRunning('example_service'));
    }

    /**
     * Test isSocktOpen method
     *
     * @return void
     */
    public function testIsSocktOpen(): void
    {
        // socket is closed
        $this->assertEquals('Offline', $this->serviceManager->isSocktOpen('127.0.0.1', 81));
    }

    /**
     * Test isProcessRunning method
     *
     * @return void
     */
    public function testIsProcessRunning(): void
    {
        exec('pgrep test_process', $output);
        $this->assertEquals([], $output);
        $this->assertFalse($this->serviceManager->isProcessRunning('test_process'));
    }

    /**
     * Test isUfwRunning method
     *
     * @return void
     */
    public function testIsUfwRunning(): void
    {
        $this->assertIsBool($this->serviceManager->isUfwRunning());
    }

    /**
     * Test isServicesListExist method
     *
     * @return void
     */
    public function testIsServicesListExist(): void
    {
        $this->assertIsBool($this->serviceManager->isServicesListExist());
    }
}
