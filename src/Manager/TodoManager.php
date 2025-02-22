<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Todo;
use DateTimeInterface;
use App\Util\SecurityUtil;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TodoManager
 *
 * Manager for todo component functionality
 *
 * @package App\Manager
 */
class TodoManager
{
    private LogManager $logManager;
    private UserManager $userManager;
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private TodoRepository $todoRepository;
    private DatabaseManager $databaseManager;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        LogManager $logManager,
        AuthManager $authManager,
        UserManager $userManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        TodoRepository $todoRepository,
        DatabaseManager $databaseManager,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->userManager = $userManager;
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
        // init todo list
        $todoList = [];

        // get todo list
        $todos = $this->todoRepository->findByUserIdAndStatus($this->authManager->getLoggedUserId(), $filter);

        // decrypt todo texts
        foreach ($todos as $todo) {
            $todoList[] = [
                'id' => $todo->getId(),
                'todoText' => $this->securityUtil->decryptAes((string) $todo->getTodoText()),
                'addedTime' => $todo->getAddedTime(),
                'completedTime' => $todo->getCompletedTime(),
                'status' => $todo->getStatus(),
                'userId' => $todo->getUserId()
            ];
        }

        // return todo list
        return $todoList;
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
     * Get todo info
     *
     * @param int $todoId The id of the todo
     *
     * @return array<string,int|string|null> The todo info
     */
    public function getTodoInfo(int $todoId): array
    {
        /** @var Todo $todo */
        $todo = $this->todoRepository->find($todoId);

        // check if todo exists
        if ($todo == null) {
            $this->errorManager->handleError(
                message: 'todo id: ' . $todoId . ' does not exist',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get todo data
        $id = $todo->getId();
        $owner = $todo->getUserId();
        $status = $todo->getStatus();
        $createdAt = $todo->getAddedTime();
        $closedAt = $todo->getCompletedTime();

        // check if owner id is set
        if ($owner != null) {
            $owner = $this->userManager->getUsernameById($owner);
        }

        // format datetimes
        if ($createdAt instanceof DateTimeInterface) {
            $createdAt = $createdAt->format('Y-m-d H:i:s');
        }
        if ($closedAt instanceof DateTimeInterface) {
            $closedAt = $closedAt->format('Y-m-d H:i:s');
        }

        // return todo info
        return [
            'id' => $id,
            'owner' => $owner ?? 'Unknown',
            'status' => $status,
            'created_at' => $createdAt ?? null,
            'closed_at' => $closedAt ?? 'non-closed'
        ];
    }

    /**
     * Create new todo
     *
     * @param string $todoText The todo text
     *
     * @return void
     */
    public function createTodo(string $todoText): void
    {
        // encrypt todo text
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

        // check if user is owner of todo
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

        // check if todo text length is valid
        if (strlen($todoText) > 2048) {
            $this->errorManager->handleError(
                message: 'todo text length is too long',
                code: Response::HTTP_BAD_REQUEST
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
     * Reopen todo by id
     *
     * @param int $todoId The todo id
     *
     * @return void
     */
    public function reopenTodo(int $todoId): void
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

        // check if todo is closed
        if ($todo->getStatus() != 'closed') {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' is not closed',
                code: Response::HTTP_FORBIDDEN
            );
        }

        try {
            // set todo status to open
            $todo->setStatus('open');
            $todo->setCompletedTime(null);

            // flush todo entity
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to reopen todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log todo reopen event
        $this->logManager->log(
            name: 'todo-manager',
            message: 'todo: ' . $todoId . ' reopened',
            level: LogManager::LEVEL_INFO
        );
    }

    /**
     * Delete todo by id
     *
     * @param int $todoId The todo id
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
