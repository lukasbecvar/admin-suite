<?php

namespace App\Tests\Controller\Api;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TerminalApiControllerTest
 *
 * Test cases for terminal API controller endpoint
 *
 * @package App\Tests\Controller\Api
 */
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertSame('Error: command is not set', $responseContent);
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertSame('Command: htop is not allowed', $responseContent);
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertNotSame('Error: command is not set', $responseContent);
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertNotEmpty($responseContent);
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

        // get response content
        $responseContent = $this->client->getResponse()->getContent();

        // check if response content is empty
        if (!$responseContent) {
            $this->fail('Response content is empty');
        }

        // assert response
        $this->assertStringContainsString('Error: directory', $responseContent);
        $this->assertStringContainsString('not found', $responseContent);
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
}
