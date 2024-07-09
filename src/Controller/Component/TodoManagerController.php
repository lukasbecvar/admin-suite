<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use App\Manager\TodoManager;
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

    public function __construct(TodoManager $todoManager, AuthManager $authManager)
    {
        $this->todoManager = $todoManager;
        $this->authManager = $authManager;
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
}
