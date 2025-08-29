<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class ServiceVisitorTrackingApiControllerTest
 *
 * Test cases for service visitor tracking api controller
 *
 * @package App\Tests\Controller\Api
 */
class ServiceVisitorTrackingApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;
    private MockObject $errorManagerMock;
    private MockObject $serviceManagerMock;
    private MockObject $metricsManagerMock;
    private MockObject $visitorInfoUtilMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // mock dependencies
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->metricsManagerMock = $this->createMock(MetricsManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);

        // set mocks in container
        self::getContainer()->set(ErrorManager::class, $this->errorManagerMock);
        self::getContainer()->set(ServiceManager::class, $this->serviceManagerMock);
        self::getContainer()->set(MetricsManager::class, $this->metricsManagerMock);
        self::getContainer()->set(VisitorInfoUtil::class, $this->visitorInfoUtilMock);
    }

    /**
     * Test visitor tracking with wrong request method
     *
     * @return void
     */
    public function testVisitorTrackingWithWrongMethod(): void
    {
        $this->client->request('GET', '/api/monitoring/visitor/tracking');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test visitor tracking when service_name parameter is not set
     *
     * @return void
     */
    public function testVisitorTrackingWithoutServiceName(): void
    {
        $this->client->request('POST', '/api/monitoring/visitor/tracking');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Parameter "service_name" is required', $responseData['message']);
    }

    /**
     * Test visitor tracking when service is not found
     *
     * @return void
     */
    public function testVisitorTrackingWithServiceNotFound(): void
    {
        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn(['some-other-service' => []]);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => 'test-service']);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Service not found', $responseData['message']);
    }

    /**
     * Test visitor tracking when service is not http type
     *
     * @return void
     */
    public function testVisitorTrackingWithWrongServiceType(): void
    {
        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn(['test-service' => ['type' => 'tcp']]);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => 'test-service']);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Service is not web http type', $responseData['message']);
    }

    /**
     * Test visitor tracking when service is unknown
     *
     * @return void
     */
    public function testVisitorTrackingWithUnknownService(): void
    {
        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn(['some-other-service' => []]);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => 'unknown-service-name']);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Service not found', $responseData['message']);
    }

    /**
     * Test visitor tracking when request uri is not valid
     *
     * @return void
     */
    public function testVisitorTrackingWithInvalidRequestUri(): void
    {
        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn(['test-service' => ['type' => 'http', 'url' => 'http://allowed-domain.com']]);

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to init visitor tracking: request uri is not allowed',
            Response::HTTP_FORBIDDEN
        );

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => 'test-service']);
    }

    /**
     * Test visitor tracking when ip is not detected
     *
     * @return void
     */
    public function testVisitorTrackingWithIpNotDetected(): void
    {
        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            'test-service' => [
                'type' => 'http',
                'url' => 'http://localhost/api/monitoring/visitor/tracking'
            ]
        ]);

        // simulate ip detection failure
        $this->visitorInfoUtilMock->method('getIP')->willReturn(null);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => 'test-service']);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('error', $responseData['status']);
        $this->assertStringContainsString('Visitor ip cannot be detected', $responseData['message']);
    }

    /**
     * Test visitor tracking for the first visit (register)
     *
     * @return void
     */
    public function testVisitorTrackingFirstVisit(): void
    {
        // testing data
        $serviceName = 'test-service';
        $ipAddress = '127.0.0.1';
        $userAgent = 'Test Agent';
        $referer = 'http://test-referer.com';

        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            $serviceName => [
                'type' => 'http',
                'url' => 'http://localhost/api/monitoring/visitor/tracking'
            ]
        ]);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn($ipAddress);
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn($userAgent);
        $this->visitorInfoUtilMock->method('getReferer')->willReturn($referer);
        $this->metricsManagerMock->method('checkIfVisitorAlreadyRegistered')->willReturn(false);
        $this->visitorInfoUtilMock->method('getIpInfo')->willReturn((object)['status' => 'success', 'countryCode' => 'TS', 'city' => 'Testville']);

        // expect visitor registration call
        $this->metricsManagerMock->expects($this->once())->method('registerServiceVisitor')
            ->with($serviceName, $ipAddress, 'TS/Testville', $referer, $userAgent);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => $serviceName]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame('success', $responseData['status']);
        $this->assertStringContainsString('Visitor registered', $responseData['message']);
    }

    /**
     * Test visitor tracking for a return visit (update)
     *
     * @return void
     */
    public function testVisitorTrackingReturnVisit(): void
    {
        // testing data
        $serviceName = 'test-service';
        $ipAddress = '127.0.0.1';
        $userAgent = 'Test Agent';
        $referer = 'http://test-referer.com';

        // mock service list
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            $serviceName => [
                'type' => 'http',
                'url' => 'http://localhost/api/monitoring/visitor/tracking'
            ]
        ]);

        // mock visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn($ipAddress);
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn($userAgent);
        $this->visitorInfoUtilMock->method('getReferer')->willReturn($referer);
        $this->metricsManagerMock->method('checkIfVisitorAlreadyRegistered')->willReturn(true);

        // expect visitor data update calls
        $this->metricsManagerMock->expects($this->once())->method('updateServiceVisitorLastVisitTime')->with($ipAddress, $serviceName);
        $this->metricsManagerMock->expects($this->once())->method('updateServiceVisitorUserAgent')->with($ipAddress, $serviceName, $userAgent);
        $this->metricsManagerMock->expects($this->once())->method('updateServiceVisitorReferer')->with($ipAddress, $serviceName, $referer);

        $this->client->request('POST', '/api/monitoring/visitor/tracking', ['service_name' => $serviceName]);

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame('success', $responseData['status']);
        $this->assertStringContainsString('Visitor data updated', $responseData['message']);
    }
}
