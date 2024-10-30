<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TodoManagerControllerTest
 *
 * Test the todo manager component
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
     * Test the todo manager load
     *
     * @return void
     */
    public function testTodoListLoad(): void
    {
        $this->client->request('GET', '/manager/todo');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorExists('input[name="create_todo_form[todo_text]"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the todo item add with empty text
     *
     * @return void
     */
    public function testTodoItemAddEmptyText(): void
    {
        // make request
        $this->client->request('POST', '/manager/todo', [
            'create_todo_form' => [
                'todo_text' => ''
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('body', 'Please enter a todo text');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the todo item add with long text
     *
     * @return void
     */
    public function testTodoItemAddLongText(): void
    {
        // make request
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
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    sdfasdfasdfasdfasdfasdfasdfasdfasdfas
                    dfasdfasdfasdfasdfasdfasdfasdfafff'
            ]
        ]);

        // assert response
        $this->assertSelectorTextContains('body', 'Your todo text cannot be longer than 512 characters');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test the todo item add
     *
     * @return void
     */
    public function testTodoItemAdd(): void
    {
        // make request
        $this->client->request('POST', '/manager/todo', [
            'create_todo_form' => [
                'todo_text' => 'todo text'
            ]
        ]);

        // assert response
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
    }
}
