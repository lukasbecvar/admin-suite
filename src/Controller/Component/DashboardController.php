<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Manager\LogManager;
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
    private AppUtil $appUtil;
    private LogManager $logManager;
    private AuthManager $authManager;

    public function __construct(AppUtil $appUtil, LogManager $logManager, AuthManager $authManager)
    {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
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
        // get warning data
        $diagnosticData = $this->appUtil->getDiagnosticData();
        $antiLogStatus = $this->logManager->isAntiLogEnabled();
        $logsCount = $this->logManager->getLogsCountWhereStatus('UNREADED');

        // return dashboard view
        return $this->render('dashboard.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // warning data
            'diagnosticData' => $diagnosticData,
            'antiLogStatus' => $antiLogStatus,
            'logsCount' => $logsCount
        ]);
    }
}
