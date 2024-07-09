<?php

namespace App\Manager;

use App\Entity\Todo;
use Doctrine\ORM\EntityManagerInterface;

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
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(LogManager $logManager, AuthManager $authManager, ErrorManager $errorManager, EntityManagerInterface $entityManagerInterface)
    {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get all todos
     *
     * @return array<mixed> The todo list
     */
    public function getTodos(): array
    {
        return $this->entityManagerInterface->getRepository(Todo::class)->findBy(['user_id' => $this->authManager->getLoggedUserId()], ['id' => 'DESC']);
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
}
