<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Todo;
use App\Util\SecurityUtil;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TodoManager
 *
 * The manager for todo component functionality
 *
 * @package App\Manager
 */
class TodoManager
{
    private LogManager $logManager;
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private TodoRepository $todoRepository;
    private DatabaseManager $databaseManager;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        LogManager $logManager,
        AuthManager $authManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        TodoRepository $todoRepository,
        DatabaseManager $databaseManager,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
        $this->todoRepository = $todoRepository;
        $this->databaseManager = $databaseManager;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get all todos
     *
     * @return array<array<mixed>> The todo list
     */
    public function getTodos(string $filter = 'open'): array
    {
        // init plain todo list
        $plainTodos = [];

        // get todo list
        $todos = $this->todoRepository->findByUserIdAndStatus($this->authManager->getLoggedUserId(), $filter);

        // decrypt todo texts
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

        // return todo list
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
        $todo = $this->todoRepository->find($todoId);

        // check if todo found
        if ($todo == null) {
            $this->errorManager->handleError(
                message: 'todo not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get todo status
        return $todo->getStatus();
    }

    /**
     * Get number of todos
     *
     * @return int The number of todos
     */
    public function getTodosCount(string $status = 'open'): int
    {
        $count = $this->todoRepository->count([
            'user_id' => $this->authManager->getLoggedUserId(),
            'status' => $status
        ]);

        return $count;
    }

    /**
     * Create a new todo
     *
     * @param string $todoText The todo text
     *
     * @throws Exception Error to persis or flush todo to database
     *
     * @return void
     */
    public function createTodo(string $todoText): void
    {
        // encrypt the todo text
        $todoText = $this->securityUtil->encryptAes($todoText);

        // create new todo entity
        $todo = new Todo();
        $todo->setTodoText($todoText)
            ->setAddedTime(new DateTime())
            ->setCompletedTime(null)
            ->setStatus('open')
            ->setUserId($this->authManager->getLoggedUserId());

        try {
            // save todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to create todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log todo create event
        $this->logManager->log(
            name: 'todo-manager',
            message: 'new todo created',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Edit todo text
     *
     * @param int $todoId The todo id
     * @param string $todoText The todo text
     *
     * @throws Exception Error to update todo entity
     *
     * @return void
     */
    public function editTodo(int $todoId, string $todoText): void
    {
        /** @var Todo $todo */
        $todo = $this->todoRepository->find($todoId);

        // check if todo is not null
        if ($todo === null) {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if user is owner of the todo
        if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        // check if todo is closed
        if ($todo->getStatus() !== 'open') {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' is closed',
                code: Response::HTTP_FORBIDDEN
            );
        }

        // encrypt todo text
        $todoText = $this->securityUtil->encryptAes($todoText);

        try {
            // set todo properties
            $todo->setTodoText($todoText);
            $todo->setCompletedTime(null);

            // save todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to edit todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log todo edit event
        $this->logManager->log(
            name: 'todo-manager',
            message: 'todo edited',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Close todo by id
     *
     * @param int $todoId The todo id
     *
     * @throws Exception Error to flush changes to database
     *
     * @return void
     */
    public function closeTodo(int $todoId): void
    {
        /** @var Todo $todo */
        $todo = $this->todoRepository->find($todoId);

        // check if todo is not null
        if ($todo === null) {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if user is owner of todo
        if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        try {
            // set todo status to closed
            $todo->setStatus('closed');
            $todo->setCompletedTime(new DateTime());

            // flush todo entity
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to close todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log todo close event
        $this->logManager->log(
            name: 'todo-manager',
            message: 'todo: ' . $todoId . ' closed',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Delete todo by id
     *
     * @param int $todoId The todo id
     *
     * @throws Exception If an error occurs while deleting the todo
     *
     * @return void
     */
    public function deleteTodo(int $todoId): void
    {
        try {
            // get todo
            $todo = $this->todoRepository->find($todoId);

            // check if todo entity is found
            if ($todo == null) {
                $this->errorManager->handleError(
                    message: 'todo not found',
                    code: Response::HTTP_NOT_FOUND
                );
            } else {
                // check if user is owner of todo
                if ($todo->getUserId() !== $this->authManager->getLoggedUserId()) {
                    $this->errorManager->handleError(
                        message: 'you are not the owner of the todo: ' . $todoId,
                        code: Response::HTTP_FORBIDDEN
                    );
                }

                // check if todo is closed
                if ($todo->getStatus() != 'closed') {
                    $this->errorManager->handleError(
                        message: 'todo: ' . $todoId . ' is not closed',
                        code: Response::HTTP_FORBIDDEN
                    );
                }

                // delete todo entity
                $this->entityManagerInterface->remove($todo);
                $this->entityManagerInterface->flush();

                // recalculate table IDs
                $this->databaseManager->recalculateTableIds($this->databaseManager->getEntityTableName(Todo::class));
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log todo delete event
        $this->logManager->log(
            name: 'todo-manager',
            message: 'todo deleted',
            level: LogManager::LEVEL_INFO
        );
    }
}
