<?php

namespace App\Controller;

use App\Manager\AuthManager;
use App\Manager\UserManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DashboardController
 *
 * Controller to handle the dashboard page.
 *
 * @package App\Controller
 */
class DashboardController extends AbstractController
{
    private AuthManager $authManager;
    private UserManager $userManager;

    public function __construct(AuthManager $authManager, UserManager $userManager)
    {
        $this->authManager = $authManager;
        $this->userManager = $userManager;
    }

    /**
     * Handle the dashboard page.
     *
     * @return Response The dashboard view
     */
    #[Route('/dashboard', methods:['GET'], name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // get current user id
        $userId = $this->authManager->getLoggedUserId();

        // get user repository
        $userRepo = $this->userManager->getUserRepo(['id' => $userId]);

        return $this->render('dashboard.html.twig', [
            'is_admin' => $this->userManager->isUserAdmin($userId),
            'user_data' => $userRepo
        ]);
    }
}
