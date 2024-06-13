<?php

namespace App\Controller;

use App\Util\AppUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use App\Form\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class UsersManagerController
 *
 * Handle users-manager component page
 *
 * @package App\Controller
 */
class UsersManagerController extends AbstractController
{
    private AppUtil $appUtil;
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(AppUtil $appUtil, UserManager $userManager, AuthManager $authManager, ErrorManager $errorManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->appUtil = $appUtil;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Handle the users-manager component.
     *
     * @return Response The users-manager view
     */
    #[Route('/manager/users', methods:['GET'], name: 'app_manager_users')]
    public function usersManager(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            $this->errorManager->handleError('You do not have permission to access this page.', 403);
        }

        // get user status filter
        $statusFilter = $request->query->getString('filter', 'all');

        // get current page from request query params
        $page = $request->query->getInt('page', 1);

        // get page limit from config
        $pageLimit = $this->appUtil->getPageLimitter();

        // get total users count from database
        $usersCount = $this->userManager->getUsersCount();

        // get users data from database
        $usersData = $this->userManager->getUsersByPage($page);

        // get online users list from auth manager
        $onlineList = $this->authManager->getOnlineUsersList();

        // render users-manager view
        return $this->render('components/manager/user/table.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository(),

            // users manager data
            'users' => $usersData,
            'online_list' => $onlineList,
            'online_count' => count($onlineList),
            'total_users_count' => $usersCount,
            'current_page' => $page,
            'limit_per_page' => $pageLimit,
            'visitor_info_util' => $this->visitorInfoUtil,
            'status_filter' => $statusFilter,
        ]);
    }

    /**
     * Handle the users-manager register component.
     *
     * @param Request $request The request object
     *
     * @return Response The users-manager register view
     */
    #[Route('/manager/users/register', methods:['GET', 'POST'], name: 'app_manager_users_register')]
    public function userRegister(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            $this->errorManager->handleError('You do not have permission to access this page.', 403);
        }

        // get page limit from config
        $pageLimit = $this->appUtil->getPageLimitter();

        // get total users count from database
        $usersCount = $this->userManager->getUsersCount();

        // create the registration form
        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        // check if the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            // get the form data
            /** @var \App\Entity\User $data */
            $data = $form->getData();

            // get the username and password
            $username = (string) $data->getUsername();
            $password = (string) $data->getPassword();

            // check if the username is already taken
            if ($this->userManager->checkIfUserExist($username)) {
                $this->addFlash('error', 'Username is already taken.');
            } else {
                // register the new user
                try {
                    $this->authManager->registerUser($username, $password);

                    // redirect to the users table page
                    return $this->redirectToRoute('app_manager_users', [
                        'page' => $this->appUtil->calculateMaxPages($usersCount, $pageLimit)
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', 'An error occurred while registering the new user.');
                }
            }
        }

        // render users-manager register view
        return $this->render('components/manager/user/register.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository(),

            // registration form
            'registration_form' => $form->createView()
        ]);
    }

    /**
     * Handle the users-manager delete component.
     *
     * @param Request $request The request object
     *
     * @return Response The users-manager delete view
     */
    #[Route('/manager/users/delete', methods:['GET', 'POST'], name: 'app_manager_users_delete')]
    public function userDelete(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            $this->errorManager->handleError('You do not have permission to access this page.', 403);
        }

        // get user id to delete
        $userId = $request->query->get('id');

        // check if user id is valid
        if ($userId == null) {
            $this->errorManager->handleError('invalid request user "id" parameter not found in query', 400);
        }

        // delete the user
        $this->userManager->deleteUser((int) $userId);

        // redirect to the users table page
        return $this->redirectToRoute('app_manager_users');
    }
}
