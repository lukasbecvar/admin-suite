<?php

namespace App\Controller\Component;

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
    private ErrorManager $errorManager;

    public function __construct(TodoManager $todoManager, ErrorManager $errorManager)
    {
        $this->todoManager = $todoManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Render todo manager component page
     *
     * @param Request $request The request object
     *
     * @return Response The todo manager component view
     */
    #[Route('/manager/todo', methods:['GET', 'POST'], name: 'app_todo_manager')]
    public function todoTable(Request $request): Response
    {
        // get query parameter filter
        $filter = (string) $request->query->get('filter', 'open');

        // get todo list
        $todos = $this->todoManager->getTodos($filter);

        // create todo create form
        $form = $this->createForm(CreateTodoFormType::class);
        $form->handleRequest($request);

        // check if form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\Todo $formData */
            $formData = $form->getData();
            $todoText = (string) $formData->getTodoText();

            // create todo
            $this->todoManager->createTodo($todoText);

            // self redirect back to todo manager
            return $this->redirectToRoute('app_todo_manager');
        }

        // return todo table page view
        return $this->render('component/todo-manager/todo-table.twig', [
            'filter' => $filter,
            'todos' => $todos,
            'createTodoForm' => $form->createView()
        ]);
    }

    /**
     * Handle todo edit functionality
     *
     * @param Request $request The request object
     *
     * @return Response The response todo manager component redirect
     */
    #[Route('/manager/todo/edit', methods:['GET'], name: 'app_todo_manager_edit')]
    public function editTodo(Request $request): Response
    {
        // get todo id
        $todoId = (int) $request->query->get('id');
        $newTodoText = (string) $request->query->get('todo');

        // check if todo id is valid
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if new todo text is valid
        if ($newTodoText == '') {
            $this->errorManager->handleError(
                message: 'invalid todo text',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // edit todo
        $this->todoManager->editTodo($todoId, $newTodoText);

        // self redirect back to todo manager
        return $this->redirectToRoute('app_todo_manager');
    }

    /**
     * Handle todo close functionality
     *
     * @param Request $request The request object
     *
     * @return Response The response todo manager component redirect
     */
    #[Route('/manager/todo/close', methods:['GET'], name: 'app_todo_manager_close')]
    public function closeTodo(Request $request): Response
    {
        // get todo id
        $todoId = (int) $request->query->get('id');

        // check if todo id is valid
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // close todo
        $this->todoManager->closeTodo($todoId);

        // self redirect back to todo manager
        return $this->redirectToRoute('app_todo_manager');
    }

    /**
     * Handle todo delete functionality
     *
     * @param Request $request The request object
     *
     * @return Response The response todo manager redirect
     */
    #[Route('/manager/todo/delete', methods:['GET'], name: 'app_todo_manager_delete')]
    public function deleteTodo(Request $request): Response
    {
        // get todo id
        $todoId = (int) $request->query->get('id');

        // check if todo id is valid
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // delete todo
        $this->todoManager->deleteTodo($todoId);

        // self redirect back to todo manager
        return $this->redirectToRoute('app_todo_manager', [
            'filter' => 'closed'
        ]);
    }
}
