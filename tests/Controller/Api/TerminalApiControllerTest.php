<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use App\Controller\Api\TerminalApiController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TerminalApiControllerTest
 *
 * Test cases for terminal API controller endpoint
 *
 * @package App\Tests\Controller\Api
 */
#[CoversClass(TerminalApiController::class)]
class TerminalApiControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->simulateLogin($this->client);
    }

    /**
     * Test execute terminal with empty command
     *
     * @return void
     */
    public function testExecuteTerminalCommandWithEmptyCommand(): void
    {
        $this->client->request('POST', '/api/system/terminal');

        /** @var array<mixed> $response */
        $response = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$response) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertSame('Error: command is not set', $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test execute terminal command with not allowed command
     *
     * @return void
     */
    public function testExecuteTerminalCommandWhenCommandIsNotAllowed(): void
    {
        $this->client->request('POST', '/api/system/terminal', [
            'command' => 'htop'
        ]);

        /** @var array<mixed> $response */
        $response = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$response) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertSame('Command: htop is not allowed', $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test execute terminal command with response is success
     *
     * @return void
     */
    public function testExecuteTerminalCommandSuccessfully(): void
    {
        $this->client->request('POST', '/api/system/terminal', [
            'command' => 'ls'
        ]);

        /** @var array<mixed> $response */
        $response = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$response) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertNotSame('Error: command is not set', $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test execute terminal command to get current path
     *
     * @return void
     */
    public function testExecuteTerminalCommandToGetCurrentPath(): void
    {
        $this->client->request('POST', '/api/system/terminal', [
            'command' => 'get_current_path_1181517815187484'
        ]);

        /** @var array<mixed> $response */
        $response = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$response) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertNotEmpty($response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test execute terminal command to change to non-existent directory
     *
     * @return void
     */
    public function testExecuteTerminalCommandToChangeToNonExistentDirectory(): void
    {
        $this->client->request('POST', '/api/system/terminal', [
            'command' => 'cd /non_existent_directory'
        ]);

        // get response data
        $response = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$response) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertStringContainsString('Error: directory', $response);
        $this->assertStringContainsString('not found', $response);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test execute terminal command with invalid method
     *
     * @return void
     */
    public function testExecuteTerminalCommandWithInvalidMethod(): void
    {
        $this->client->request('GET', '/api/system/terminal');

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Test start background job with empty command
     *
     * @return void
     */
    public function testStartTerminalJobWithEmptyCommand(): void
    {
        $this->client->request('POST', '/api/system/terminal/job');

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $response['status']);
        $this->assertSame('Error: command is not set', $response['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test start background job with blocked command
     *
     * @return void
     */
    public function testStartTerminalJobWithBlockedCommand(): void
    {
        $this->client->request('POST', '/api/system/terminal/job', [
            'command' => 'htop'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('blocked', $response['status']);
        $this->assertStringContainsString('htop', $response['message']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test start background job returns JSON payload even when execution fails
     *
     * @return void
     */
    public function testStartTerminalJobReturnsJsonPayload(): void
    {
        $this->client->request('POST', '/api/system/terminal/job', [
            'command' => 'echo async-test'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertArrayHasKey('status', $response);
        if ($response['status'] === 'running') {
            $this->assertArrayHasKey('jobId', $response);
        } else {
            $this->assertArrayHasKey('message', $response);
        }
    }

    /**
     * Test polling background job status for unknown job identifier
     *
     * @return void
     */
    public function testGetTerminalJobStatusWithInvalidIdentifier(): void
    {
        $this->client->request('GET', '/api/system/terminal/job', [
            'jobId' => 'invalid-job-id'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $response['status']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test stopping non-existing background job responds gracefully
     *
     * @return void
     */
    public function testStopTerminalJobForUnknownProcess(): void
    {
        $this->client->request('POST', '/api/system/terminal/job/stop', [
            'jobId' => 'invalid-job-id'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('stopped', $response['status']);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test sending input without payload fails
     *
     * @return void
     */
    public function testSendTerminalJobInputWithoutPayload(): void
    {
        $this->client->request('POST', '/api/system/terminal/job/input', [
            'jobId' => 'example'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $response['status']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test sending input for unknown job returns error response
     *
     * @return void
     */
    public function testSendTerminalJobInputForUnknownJob(): void
    {
        $this->client->request('POST', '/api/system/terminal/job/input', [
            'jobId' => 'example',
            'input' => 'y'
        ]);

        /** @var array<mixed> $response */
        $response = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertSame('error', $response['status']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
