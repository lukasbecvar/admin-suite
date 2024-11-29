<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ServiceManagerTest
 *
 * Test cases for service manager
 *
 * @package App\Tests\Manager
 */
class ServiceManagerTest extends TestCase
{
    private ServiceManager $serviceManager;
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManager;
    private AuthManager & MockObject $authManager;
    private ErrorManager & MockObject $errorManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // create the service manager instance
        $this->serviceManager = new ServiceManager(
            $this->appUtilMock,
            $this->logManager,
            $this->authManager,
            $this->errorManager
        );
    }

    /**
     * Test check is service running
     *
     * @return void
     */
    public function testIsServiceRunning(): void
    {
        // call tested method
        $result = $this->serviceManager->isServiceRunning('test_service');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check is process running
     *
     * @return void
     */
    public function testIsProcessRunning(): void
    {
        // call tested method
        $result = $this->serviceManager->isProcessRunning('test_process');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check is ufw firewall running
     *
     * @return void
     */
    public function testIsUfwRunning(): void
    {
        // call tested method
        $result = $this->serviceManager->isUfwRunning();

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test check is services list exist
     *
     * @return void
     */
    public function testIsServicesListExist(): void
    {
        // call tested method
        $result = $this->serviceManager->isServicesListExist();

        // assert result
        $this->assertIsBool($result);
    }
}
