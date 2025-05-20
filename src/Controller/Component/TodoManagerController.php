<?php

namespace App\Controller\Component;

use Exception;
use App\Manager\TodoManager;
use App\Manager\ErrorManager;
use App\Form\Todo\CreateTodoFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
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

            try {
                // save new todo to database
                $this->todoManager->createTodo($todoText);

                // redirect back to todo manager
                return $this->redirectToRoute('app_todo_manager');
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to create todo: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
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
     * @return Response The redirect back to todo manager
     */
    #[Route('/manager/todo/edit', methods:['GET'], name: 'app_todo_manager_edit')]
    public function editTodo(Request $request): Response
    {
        // get request parameters
        $todoId = (int) $request->query->get('id');
        $newTodoText = (string) $request->query->get('todo');

        // check if todo id is valid or not set
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // check if new todo text is valid or not set
        if (empty($newTodoText)) {
            $this->errorManager->handleError(
                message: 'todo text parameter are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // edit todo
            $this->todoManager->editTodo($todoId, $newTodoText);

            // redirect back to todo manager
            return $this->redirectToRoute('app_todo_manager');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to edit todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle todo close functionality
     *
     * @param Request $request The request object
     *
     * @return Response The redirect back to todo manager
     */
    #[Route('/manager/todo/close', methods:['GET'], name: 'app_todo_manager_close')]
    public function closeTodo(Request $request): Response
    {
        // get todo id from request parameter
        $todoId = (int) $request->query->get('id');

        // check if todo id is valid or not set
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // close todo
            $this->todoManager->closeTodo($todoId);

            // redirect back to todo manager
            return $this->redirectToRoute('app_todo_manager');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to close todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle todo reopen functionality
     *
     * @param Request $request The request object
     *
     * @return Response The redirect back to todo manager
     */
    #[Route('/manager/todo/reopen', methods:['GET'], name: 'app_todo_manager_reopen')]
    public function reopenTodo(Request $request): Response
    {
        // get todo id from request parameter
        $todoId = (int) $request->query->get('id', '0');

        // check if todo id is valid or not set
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'invalid todo id',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // reopen todo
            $this->todoManager->reopenTodo($todoId);

            // redirect back to todo manager
            return $this->redirectToRoute('app_todo_manager');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to reopen todo: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
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
        // get todo id from request parameter
        $todoId = (int) $request->query->get('id', '0');

        // check if todo id is valid or not set
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

    /**
     * Get todo info
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The todo info in json format
     */
    #[Route('/manager/todo/info', methods:['GET'], name: 'app_todo_manager_info')]
    public function getTodoInfo(Request $request): JsonResponse
    {
        // get todo id
        $todoId = (int) $request->query->get('id', '0');

        // check if todo id is valid
        if ($todoId == 0) {
            $this->errorManager->handleError(
                message: 'todo id is invalid or not set',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // get todo info
            $todoInfo = $this->todoManager->getTodoInfo($todoId);

            // return todo info in json format
            return $this->json($todoInfo, Response::HTTP_OK);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get todo info: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update todo positions
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The response with success status
     */
    #[Route('/manager/todo/update-positions', methods:['POST'], name: 'app_todo_manager_update_positions')]
    public function updateTodoPositions(Request $request): JsonResponse
    {
        try {
            // get positions data from request
            $positions = json_decode($request->getContent(), true);

            // validate positions data
            if (!is_array($positions) || empty($positions)) {
                $this->errorManager->logError(
                    message: 'invalid positions data',
                    code: Response::HTTP_BAD_REQUEST
                );
                return $this->json(['success' => false, 'message' => 'Invalid positions data'], Response::HTTP_BAD_REQUEST);
            }

            // update positions
            $this->todoManager->updateTodoPositions($positions);

            // return success response
            return $this->json(['success' => true], Response::HTTP_OK);
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'error updating positions: ' . $e->getMessage(),
                code: $e->getCode()
            );
            return $this->json(
                ['success' => false, 'message' => 'Error updating positions: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
