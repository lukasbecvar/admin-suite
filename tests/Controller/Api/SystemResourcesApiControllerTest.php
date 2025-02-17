<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class SystemResourcesApiControllerTest
 *
 * Test cases for get system resources data API endpoint
 *
 * @package App\Tests\Controller\Api
 */
class SystemResourcesApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test get system resources data with invalid method
     *
     * @return void
     */
    public function testGetSystemResourcesDataWithInvalidMethod(): void
    {
        $this->client->request('POST', '/api/system/resources');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test get system resources data when login session is not set
     *
     * @return void
     */
    public function testGetSystemResourcesDataWhenLoginSessionIsNotSet(): void
    {
        $this->client->request('GET', '/api/system/resources');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test get system resources data when login session is set
     *
     * @return void
     */
    public function testGetSystemResourcesDataWhenLoginSessionIsSet(): void
    {
        // simulate login session
        $this->simulateLogin($this->client);

        // request system resources data
        $this->client->request('GET', '/api/system/resources');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertArrayHasKey('ramUsage', $responseData);
        $this->assertArrayHasKey('hostUptime', $responseData);
        $this->assertArrayHasKey('storageUsage', $responseData);
        $this->assertArrayHasKey('diagnosticData', $responseData);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
