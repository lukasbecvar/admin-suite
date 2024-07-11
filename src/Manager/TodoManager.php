<?php

namespace App\Manager;

use App\Entity\Todo;
use App\Util\SecurityUtil;
use Doctrine\ORM\EntityManagerInterface;

use function Symfony\Component\String\b;

/**
 * Class TodoManager
 *
 * Manager for todo entity operations
 *
 * @package App\Manager
 */
class TodoManager
{
    private LogManager $logManager;
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        LogManager $logManager,
        AuthManager $authManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get all todos
     *
     * @return array<mixed> The todo list
     */
    public function getTodos(string $filter = 'open'): array
    {
        // init the plain todo list
        $plainTodos = [];

        // get the todo list
        $todos = $this->entityManagerInterface->getRepository(Todo::class)->findBy(['user_id' => $this->authManager->getLoggedUserId(), 'status' => $filter], ['id' => 'DESC']);

        // decrypt the todo texts
        foreach ($todos as $todo) {
            $plainTodos[] = [
                'id' => $todo->getId(),
                'todoText' => $this->securityUtil->decryptAes((string) $todo->getTodoText()),
                'addedTime' => $todo->getAddedTime(),
                'completedTime' => $todo->getCompletedTime(),
                'status' => $todo->getStatus(),
                'userId' => $todo->getUserId()
            ];
        }

        // return the plain todo list
        return $plainTodos;
    }

    /**
     * Get todo status
     *
     * @param int $todoId The todo id
     *
     * @return string|null The todo status
     */
    public function getTodoStatus(int $todoId): ?string
    {
        /** @var Todo $todo */
        $todo = $this->entityManagerInterface->getRepository(Todo::class)->find($todoId);

        return $todo->getStatus();
    }

    /**
     * Create a new todo
     *
     * @param string $todoText The todo text
     *
     * @return void
     */
    public function createTodo(string $todoText): void
    {
        // create the todo entity
        $todo = new Todo();

        // encrypt the todo text
        $todoText = $this->securityUtil->encryptAes($todoText);

        try {
            // set todo properties
            $todo->setTodoText($todoText);
            $todo->setAddedTime(new \DateTime());
            $todo->setCompletedTime(null);
            $todo->setStatus('open');
            $todo->setUserId($this->authManager->getLoggedUserId());

            // save the todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();

            // log the todo creation
            $this->logManager->log('todo-manager', 'new todo created', 3);
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to create todo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Edit a todo
     *
     * @param int $todoId The todo id
     * @param string $todoText The todo text
     *
     * @return void
     */
    public function editTodo(int $todoId, string $todoText): void
    {
        /** @var Todo $todo */
        $todo = $this->entityManagerInterface->getRepository(Todo::class)->find($todoId);

        // check if the todo is not null
        if ($todo === null) {
            $this->errorManager->handleError('todo: ' . $todoId . ' not found', 404);
        }

        // check if the user is the owner of the todo
        if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
            $this->errorManager->handleError('you are not the owner of the todo: ' . $todoId, 403);
        }

        // check if the todo is closed
        if ($todo->getStatus() !== 'open') {
            $this->errorManager->handleError('todo: ' . $todoId . ' is closed', 403);
        }

        // encrypt the todo text
        $todoText = $this->securityUtil->encryptAes($todoText);

        try {
            // set todo properties
            $todo->setTodoText($todoText);
            $todo->setCompletedTime(null);

            // save the todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();

            // log the todo creation
            $this->logManager->log('todo-manager', 'todo edited', 3);
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to edit todo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Close a todo
     *
     * @param int $todoId The todo id
     *
     * @return void
     */
    public function closeTodo(int $todoId): void
    {
        /** @var Todo $todo */
        $todo = $this->entityManagerInterface->getRepository(Todo::class)->find($todoId);

        // check if the todo is not null
        if ($todo === null) {
            $this->errorManager->handleError('todo: ' . $todoId . ' not found', 404);
        }

        // check if the user is the owner of the todo
        if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
            $this->errorManager->handleError('you are not the owner of the todo: ' . $todoId, 403);
        }

        // check if the todo is closed
        if ($todo->getStatus() !== 'open') {
            $this->errorManager->handleError('todo: ' . $todoId . ' is already closed', 403);
        }

        try {
            // set the todo status to closed
            $todo->setStatus('closed');
            $todo->setCompletedTime(new \DateTime());

            // save the todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();

            // log the todo creation
            $this->logManager->log('todo-manager', 'todo closed', 3);
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to close todo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a todo
     *
     * @param int $todoId The todo id
     *
     * @return void
     */
    public function deleteTodo(int $todoId): void
    {
        try {
            // get the todo entity
            $todo = $this->entityManagerInterface->getRepository(Todo::class)->find($todoId);

            // check if the todo entity is found
            if ($todo == null) {
                $this->errorManager->handleError('todo not found', 404);
            } else {
                // check if the user is the owner of the todo
                if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
                    $this->errorManager->handleError('you are not the owner of the todo: ' . $todoId, 403);
                }

                // check if the todo is closed
                if ($todo->getStatus() != 'closed') {
                    $this->errorManager->handleError('todo: ' . $todoId . ' is not closed', 403);
                }

                // delete the todo entity
                $this->entityManagerInterface->remove($todo);
                $this->entityManagerInterface->flush();

                // log the todo deletion
                $this->logManager->log('todo-manager', 'todo deleted', 3);
            }
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to delete todo: ' . $e->getMessage(), 500);
        }
    }
}
