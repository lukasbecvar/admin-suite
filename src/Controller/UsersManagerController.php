<?php

namespace App\Controller;

use App\Util\AppUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
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
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(AppUtil $appUtil, UserManager $userManager, AuthManager $authManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->appUtil = $appUtil;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
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
        return $this->render('manager/user/table.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository(),

            // users manager data
            'users' => $usersData,
            'online_list' => $onlineList,
            'online_count' => count($onlineList),
            'total_users_count' => $usersCount,
            'current_page' => $page,
            'limit_per_page' => $pageLimit,
            'visitor_info_util' => $this->visitorInfoUtil
        ]);
    }

    /**
     * Handle the users-manager register component.
     *
     * @return Response The users-manager register view
     */
    #[Route('/manager/users/register', methods:['GET', 'POST'], name: 'app_manager_users_register')]
    public function userRegister(): Response
    {
        // render users-manager register view
        return $this->render('manager/user/register.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
