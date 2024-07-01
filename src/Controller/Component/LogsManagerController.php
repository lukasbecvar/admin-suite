<?php

namespace App\Controller\Component;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
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
    private LogManager $logManager;
    private UserManager $userManager;
    private AuthManager $authManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
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
            'userData' => $this->authManager->getLoggedUserRepository(),

            // instances for logs manager view
            'userManager' => $this->userManager,
            'visitorInfoUtil' => $this->visitorInfoUtil,

            'logsCount' => $this->logManager->getLogsCountWhereStatus('UNREADED'),
            'logs' => $this->logManager->getLogsWhereStatus('UNREADED')
        ]);
    }
}
