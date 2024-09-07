<?php

namespace App\Tests\Manager;

use App\Entity\Todo;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\TodoManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class TodoManagerTest
 *
 * Test for the TodoManager class
 *
 * @package App\Tests\Manager
 */
class TodoManagerTest extends TestCase
{
    private TodoManager $todoManager;
    private LogManager & MockObject $logManagerMock;
    private AuthManager & MockObject $authManagerMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // initialize the TodoManager with the mocked dependencies
        $this->todoManager = new TodoManager(
            $this->logManagerMock,
            $this->authManagerMock,
            $this->securityUtilMock,
            $this->errorManagerMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test get todos
     *
     * @return void
     */
    public function testGetTodos(): void
    {
        $userId = 1;
        $filter = 'open';

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($userId);

        // mock todo entity
        $todo = new Todo();
        $todo->setTodoText('encrypted text')
            ->setAddedTime(new \DateTime())
            ->setCompletedTime(null)
            ->setStatus('open')
            ->setUserId($userId);

        // mock todo repository
        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock->method('findBy')->willReturn([$todo]);

        // mock entity manager
        $this->entityManagerMock->method('getRepository')->willReturn($todoRepositoryMock);

        // mock security util
        $this->securityUtilMock->method('decryptAes')->willReturn('decrypted text');

        // call method
        $todos = $this->todoManager->getTodos($filter);

        // assert the result
        $this->assertIsArray($todos);
        $this->assertCount(1, $todos);
        $this->assertArrayHasKey('todoText', $todos[0]);
        $this->assertEquals('decrypted text', $todos[0]['todoText']);
    }

    /**
     * Test create todo
     *
     * @return void
     */
    public function testCreateTodo(): void
    {
        $userId = 1;
        $todoText = 'test todo';

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($userId);

        // mock security util
        $this->securityUtilMock->method('encryptAes')->willReturn('encrypted text');

        // mock log manager
        $this->logManagerMock->expects($this->once())
            ->method('log')
            ->with('todo-manager', 'new todo created', 4);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Todo::class));
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // call method
        $this->todoManager->createTodo($todoText);
    }

    /**
     * Test edit todo
     *
     * @return void
     */
    public function testEditTodo(): void
    {
        $userId = 1;
        $todoId = 1;
        $newText = 'updated todo';

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($userId);

        // mock todo entity
        $todo = new Todo();
        $todo->setTodoText('encrypted text')
            ->setAddedTime(new \DateTime())
            ->setCompletedTime(null)
            ->setStatus('open')
            ->setUserId($userId);

        // mock todo repository
        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock->method('find')->willReturn($todo);

        // mock entity manager
        $this->entityManagerMock->method('getRepository')->willReturn($todoRepositoryMock);

        // mock security util
        $this->securityUtilMock->method('encryptAes')->willReturn('encrypted updated text');

        // mock log manager
        $this->logManagerMock->expects($this->once())
            ->method('log')
            ->with('todo-manager', 'todo edited', 4);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Todo::class));
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // call method
        $this->todoManager->editTodo($todoId, $newText);
    }

    /**
     * Test close todo
     *
     * @return void
     */
    public function testCloseTodo(): void
    {
        $userId = 1;
        $todoId = 1;

        // mock auth manager
        $this->authManagerMock->method('getLoggedUserId')->willReturn($userId);

        // mock todo entity
        $todo = new Todo();
        $todo->setTodoText('encrypted text')
            ->setAddedTime(new \DateTime())
            ->setCompletedTime(null)
            ->setStatus('open')
            ->setUserId($userId);

        // mock todo repository
        $todoRepositoryMock = $this->createMock(TodoRepository::class);
        $todoRepositoryMock->method('find')->willReturn($todo);

        // mock entity manager
        $this->entityManagerMock->method('getRepository')->willReturn($todoRepositoryMock);

        // mock log manager
        $this->logManagerMock->expects($this->once())
            ->method('log')
            ->with('todo-manager', 'todo closed', 4);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Todo::class));
        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // call method
        $this->todoManager->closeTodo($todoId);
    }
}
