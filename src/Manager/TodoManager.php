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
    private const STATUS_OPEN = 'open';
    private const STATUS_CLOSED = 'closed';

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
        UserManager $userManager,
        AuthManager $authManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        TodoRepository $todoRepository,
        DatabaseManager $databaseManager,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
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
                'userId' => $todo->getUser()?->getId(),
                'position' => $todo->getPosition()
            ];
        }

        // sort completed todos by completed time
        if ($filter == self::STATUS_CLOSED) {
            usort($todoList, function ($a, $b) {
                return $a['completedTime'] <=> $b['completedTime'];
            });
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
        $user = $this->userManager->getUserReference($this->authManager->getLoggedUserId());
        if ($user === null) {
            return 0;
        }

        return $this->todoRepository->count([
            'user' => $user,
            'status' => $status
        ]);
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
        $owner = $todo->getUser();
        $status = $todo->getStatus();
        $createdAt = $todo->getAddedTime();
        $closedAt = $todo->getCompletedTime();

        // check if owner id is set
        $ownerName = $owner?->getUsername();

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
            'owner' => $ownerName ?? 'Unknown',
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

        // get the highest position
        $highestPosition = 0;
        $userTodos = $this->todoRepository->findByUserIdAndStatus($this->authManager->getLoggedUserId(), self::STATUS_OPEN);
        foreach ($userTodos as $userTodo) {
            $position = $userTodo->getPosition();
            if ($position > $highestPosition) {
                $highestPosition = $position;
            }
        }

        // get logged user reference
        $user = $this->userManager->getUserReference($this->authManager->getLoggedUserId());
        if ($user === null) {
            $this->errorManager->handleError(
                message: 'unable to resolve logged user for todo creation',
                code: Response::HTTP_UNAUTHORIZED
            );
        }

        // create new todo entity
        $todo = new Todo();
        $todo->setTodoText($todoText)
            ->setAddedTime(new DateTime())
            ->setCompletedTime(null)
            ->setStatus(self::STATUS_OPEN)
            ->setUser($user)
            ->setPosition($highestPosition + 1);

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
        if (!$this->isTodoOwnedByLoggedUser($todo)) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        // check if todo is closed
        if ($todo->getStatus() !== self::STATUS_OPEN) {
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
        if (!$this->isTodoOwnedByLoggedUser($todo)) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        try {
            // set todo status to closed
            $todo->setStatus(self::STATUS_CLOSED);
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
        if (!$this->isTodoOwnedByLoggedUser($todo)) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        // check if todo is closed
        if ($todo->getStatus() != self::STATUS_CLOSED) {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' is not closed',
                code: Response::HTTP_FORBIDDEN
            );
        }

        try {
            // set todo status to open
            $todo->setStatus(self::STATUS_OPEN);
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
                if (!$this->isTodoOwnedByLoggedUser($todo)) {
                    $this->errorManager->handleError(
                        message: 'you are not the owner of the todo: ' . $todoId,
                        code: Response::HTTP_FORBIDDEN
                    );
                }

                // check if todo is closed
                if ($todo->getStatus() != self::STATUS_CLOSED) {
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

    /**
     * Update todo position
     *
     * @param int $todoId The todo id
     * @param int $newPosition The new position
     *
     * @return void
     */
    public function updateTodoPosition(int $todoId, int $newPosition): void
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
        if (!$this->isTodoOwnedByLoggedUser($todo)) {
            $this->errorManager->handleError(
                message: 'you are not the owner of the todo: ' . $todoId,
                code: Response::HTTP_FORBIDDEN
            );
        }

        // check if todo is open
        if ($todo->getStatus() !== self::STATUS_OPEN) {
            $this->errorManager->handleError(
                message: 'todo: ' . $todoId . ' is not open',
                code: Response::HTTP_FORBIDDEN
            );
        }

        try {
            // set new position
            $todo->setPosition($newPosition);

            // save todo entity
            $this->entityManagerInterface->persist($todo);
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error updating todo position: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update multiple todo positions
     *
     * @param array<int, int> $positions Array of todo IDs and their new positions
     *
     * @return void
     */
    public function updateTodoPositions(array $positions): void
    {
        try {
            foreach ($positions as $todoId => $position) {
                /** @var Todo $todo */
                $todo = $this->todoRepository->find($todoId);

                // skip if todo not found or user is not the owner
                if ($todo === null || !$this->isTodoOwnedByLoggedUser($todo) || $todo->getStatus() !== self::STATUS_OPEN) {
                    continue;
                }

                // update position
                $todo->setPosition($position);
                $this->entityManagerInterface->persist($todo);
            }

            // save all changes to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error updating todo positions: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Re-encrypt all todos
     *
     * @param string $oldKey The old encryption key
     * @param string $newKey The new encryption key
     *
     * @return void
     */
    public function reEncryptTodos(string $oldKey, string $newKey): void
    {
        // get all todos
        $todos = $this->todoRepository->findAll();

        foreach ($todos as $todo) {
            // get todo text
            $todoText = $todo->getTodoText();
            if ($todoText === null) {
                continue;
            }

            // decrypt todo text
            $todoText = $this->securityUtil->decryptAes(encryptedData: $todoText, key: $oldKey);
            if ($todoText === null) {
                $this->errorManager->handleError(
                    message: 'error decrypting todo text',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // encrypt todo text
            $todoText = $this->securityUtil->encryptAes(plainText: $todoText, key: $newKey);

            // set new todo text
            $todo->setTodoText($todoText);
            $this->entityManagerInterface->persist($todo);
        }

        // save all changes to database
        $this->entityManagerInterface->flush();
    }

    /**
     * Check if todo is owned by logged user
     *
     * @param Todo|null $todo The todo to check
     *
     * @return bool True if todo is owned by logged user, false otherwise
     */
    public function isTodoOwnedByLoggedUser(?Todo $todo): bool
    {
        if ($todo === null) {
            return false;
        }

        return ($todo->getUser()?->getId() ?? 0) === $this->authManager->getLoggedUserId();
    }
}
