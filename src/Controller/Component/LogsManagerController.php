<?php

namespace App\Controller\Component;

use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LogsManagerController
 *
 * Controller to manage or display of logs
 *
 * @package App\Controller\Component
 */
class LogsManagerController extends AbstractController
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Display the logs table view.
     *
     * @return Response The response object containing the rendered view.
     */
    #[Route('/manager/logs', methods:['GET'], name: 'app_manager_logs')]
    public function logsTable(): Response
    {
        // return logs table view
        return $this->render('component/logs-manager/logs-table.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository()
        ]);
    }
}
