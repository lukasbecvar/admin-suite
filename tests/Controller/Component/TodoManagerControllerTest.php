<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TodoManagerControllerTest
 *
 * Test cases for todo manager component
 *
 * @package App\Tests\Controller\Component
 */
class TodoManagerControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load todo manager page
     *
     * @return void
     */
    public function testLoadTodoManagerPage(): void
    {
        $this->client->request('GET', '/manager/todo');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="View closed todos"]');
        $this->assertSelectorExists('input[name="create_todo_form[todo_text]"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load completed todos page
     *
     * @return void
     */
    public function testLoadCompletedTodosPage(): void
    {
        $this->client->request('GET', '/manager/todo', [
            'filter' => 'closed'
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[title="View open todos"]');
        $this->assertSelectorNotExists('input[name="create_todo_form[todo_text]"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test get todo info
     *
     * @return void
     */
    public function testGetTodoInfo(): void
    {
        $this->client->request('GET', '/manager/todo/info?id=1');

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('owner', $responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('created_at', $responseData);
        $this->assertArrayHasKey('closed_at', $responseData);
        $this->assertResponseStatusCodeSame(JsonResponse::HTTP_OK);
    }

    /**
     * Test submit todo add form with empty todo text
     *
     * @return void
     */
    public function testSubmitTodoAddFormWithEmptyText(): void
    {
        $this->client->request('POST', '/manager/todo', [
            'create_todo_form' => [
                'todo_text' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorTextContains('body', 'Please enter a todo text');
        $this->assertSelectorExists('input[name="create_todo_form[todo_text]"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit todo add form with todo text longer than maximum
     *
     * @return void
     */
    public function testSubmitTodoAddFormWithTodoTextLongerThanMaximum(): void
    {
        $this->client->request('POST', '/manager/todo', [
            'create_todo_form' => [
                'todo_text' => '
                    asdfasdfasdfasdfasdfasdfasdfasdfasdfa
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    dfasdfasdfasdfasdfasdfasdfasdfasdfasd
                    fasdfasdfasdfasdfasdfasdfasdfasdfasdf
                    asdfasdfasdfasdfasdfasdfasdfasdfasdfa
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    asdfasdfasdfasdfasdfasdfasdfasdfasdfa
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    dfasdfasdfasdfasdfasdfasdfasdfasdfasd
                    fasdfasdfasdfasdfasdfasdfasdfasdfasdf
                    asdfasdfasdfasdfasdfasdfasdfasdfasdfa
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                '
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorTextContains('body', 'Your todo text cannot be longer than 1024 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test submit todo add form with success response
     *
     * @return void
     */
    public function testSubmitTodoAddFormWithSuccessResponse(): void
    {
        $this->client->request('POST', '/manager/todo', [
            'create_todo_form' => [
                'todo_text' => 'todo text'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }

    /**
     * Test update todo positions with valid data
     *
     * @return void
     */
    public function testUpdateTodoPositionsWithValidData(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/manager/todo/update-positions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                ['id' => 1, 'position' => 1],
                ['id' => 2, 'position' => 2],
                ['id' => 3, 'position' => 3]
            ]) ?: '{}'
        );

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    /**
     * Test update todo positions with invalid data
     *
     * @return void
     */
    public function testUpdateTodoPositionsWithInvalidData(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/manager/todo/update-positions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([]) ?: '{}'
        );

        /** @var array<mixed> $responseData */
        $responseData = json_decode(($this->client->getResponse()->getContent() ?: '{}'), true);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Invalid positions data', $responseData['message']);
    }
}
