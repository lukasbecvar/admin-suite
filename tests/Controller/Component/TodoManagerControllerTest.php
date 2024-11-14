<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Class TodoManagerControllerTest
 *
 * Test for todo manager component
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
        $this->assertSelectorNotExists('input[name="create_todo_form[todo_text]"]');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
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
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Todo list');
        $this->assertSelectorTextContains('body', 'Your todo text cannot be longer than 512 characters');
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
}
