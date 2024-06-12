<?php

namespace App\Controller;

use App\Manager\AuthManager;
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
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle the users-manager component.
     *
     * @return Response The users-manager view
     */
    #[Route('/manager/users', methods:['GET'], name: 'app_manager_users')]
    public function usersManager(): Response
    {
        // return user-manager view
        return $this->render('users-manager.twig', [
            'is_admin' => $this->authManager->isLoggedInUserAdmin(),
            'user_data' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
