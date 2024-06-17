<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DashboardController
 *
 * Controller to handle the dashboard component
 *
 * @package App\Controller
 */
class DashboardController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Handle the dashboard page view
     *
     * @return Response The dashboard view
     */
    #[Route('/dashboard', methods:['GET'], name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // return dashboard view
        return $this->render('dashboard.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
