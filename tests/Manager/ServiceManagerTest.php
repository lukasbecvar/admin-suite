<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ServiceManagerTest
 *
 * Test cases for service manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(ServiceManager::class)]
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

        // create service manager instance
        $this->serviceManager = new ServiceManager(
            $this->appUtilMock,
            $this->logManager,
            $this->authManager,
            $this->errorManager
        );
    }

    /**
     * Test get services list
     *
     * @return void
     */
    public function testGetServicesList(): void
    {
        // expect call config load
        $this->appUtilMock->expects($this->once())->method('loadConfig');

        // call tested method
        $this->serviceManager->getServicesList();
    }

    /**
     * Test run systemd action
     *
     * @return void
     */
    public function testRunSystemdAction(): void
    {
        // mock user logged in
        $this->authManager->method('isUserLogedin')->willReturn(true);
        $userMock = $this->createMock(User::class);
        $userMock->method('getUsername')->willReturn('test_user');
        $this->authManager->method('getLoggedUserRepository')->willReturn($userMock);

        // expect call log manager
        $this->logManager->expects($this->once())->method('log')->with(
            'action-runner',
            'test_user start test_service',
            LogManager::LEVEL_WARNING
        );

        // call tested method
        $this->serviceManager->runSystemdAction('test_service', 'start');
    }

    /**
     * Test check if service is running
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
     * Test check if ufw firewall is running
     *
     * @return void
     */
    public function testCheckIfUfwIsRunning(): void
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

    /**
     * Test check website status
     *
     * @return void
     */
    public function testCheckWebsiteStatus(): void
    {
        // call tested method with a reliable website
        $result = $this->serviceManager->checkWebsiteStatus('https://www.google.com');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('isOnline', $result);
        $this->assertArrayHasKey('responseTime', $result);
        $this->assertArrayHasKey('responseCode', $result);
        $this->assertIsBool($result['isOnline']);
        $this->assertIsFloat($result['responseTime']);
        $this->assertIsInt($result['responseCode']);
    }
}
