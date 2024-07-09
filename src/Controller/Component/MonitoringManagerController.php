<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MonitoringManagerController
 *
 * Handles the the monitoring manager page
 *
 * @package App\Controller\Component
 */
class MonitoringManagerController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Renders the monitoring manager page
     *
     * @return Response The rendered monitoring manager page view
     */
    #[Route('/manager/monitoring', methods:['GET'], name: 'app_manager_monitoring')]
    public function monitoring(): Response
    {
        // return about view
        return $this->render('component/monitoring-manager/monitoring-dashboard.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
