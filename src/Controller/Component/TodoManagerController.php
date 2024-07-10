<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use App\Manager\TodoManager;
use App\Manager\ErrorManager;
use App\Form\Todo\CreateTodoFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class TodoManagerController
 *
 * Controller for todo manager component
 *
 * @package App\Controller\Component
 */
class TodoManagerController extends AbstractController
{
    private TodoManager $todoManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;

    public function __construct(TodoManager $todoManager, AuthManager $authManager, ErrorManager $errorManager)
    {
        $this->todoManager = $todoManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle the todo manager component
     *
     * @param Request $request The request object
     *
     * @return Response The response todo manager component view
     */
    #[Route('/manager/todo', methods:['GET', 'POST'], name: 'app_todo_manager')]
    public function todoTable(Request $request): Response
    {
        // get todo list
        $todos = $this->todoManager->getTodos();

        // create the todo create form
        $form = $this->createForm(CreateTodoFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\Todo $formData */
            $formData = $form->getData();
            $todoText = (string) $formData->getTodoText();

            // create the todo
            $this->todoManager->createTodo($todoText);

            // self redirect back to todo manager
            return $this->redirectToRoute('app_todo_manager');
        }

        // return view
        return $this->render('component/todo-manager/todo-table.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // todo manager data
            'todos' => $todos,
            'createTodoForm' => $form->createView()
        ]);
    }

    /**
     * Handle the todo close function
     *
     * @param Request $request The request object
     *
     * @return Response The response todo manager close component view
     */
    #[Route('/manager/todo/close', methods:['GET'], name: 'app_todo_manager_close')]
    public function closeTodo(Request $request): Response
    {
        // get todo id
        $todoId = (int) $request->query->get('id');

        // check if the todo id is valid
        if ($todoId == 0) { 
            $this->errorManager->handleError('invalid todo id', 400);
        }

        // close the todo
        $this->todoManager->closeTodo($todoId);

        // self redirect back to todo manager
        return $this->redirectToRoute('app_todo_manager');
    }
}
