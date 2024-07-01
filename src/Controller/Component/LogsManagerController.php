<?php

namespace App\Controller\Component;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Request $request The request object
     *
     * @return Response The response object containing the rendered view.
     */
    #[Route('/manager/logs', methods:['GET'], name: 'app_manager_logs')]
    public function logsTable(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get filter from request query params
        $filter = $request->query->get('filter', 'UNREADED');

        // get user id from query param
        $userId = $request->query->get('user_id', '0');

        // return logs table view
        return $this->render('component/logs-manager/logs-table.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // instances for logs manager view
            'userManager' => $this->userManager,
            'visitorInfoUtil' => $this->visitorInfoUtil,

            // logs data
            'logsCount' => $this->logManager->getLogsCountWhereStatus($filter, (int) $userId),
            'logs' => $this->logManager->getLogsWhereStatus($filter, (int) $userId),

            // anti log data
            'antiLogEnabled' => $this->logManager->isAntiLogEnabled(),

            // filter helpers
            'filter' => $filter
        ]);
    }

    /**
     * Sets logs to 'READED'.
     *
     * @param Request $request The request object
     *
     * @return Response Redirects to the dashboard page after setting logs to 'READED'.
     */
    #[Route('/manager/logs/set/readed', methods:['GET'], name: 'app_manager_logs_set_readed')]
    public function setAllLogsToReaded(Request $request): Response
    {
        // check if user have admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        $id = $request->get('id', 0);

        // validate and cast id to int
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

        // set all logs to readed
        if ($id == 0) {
            $this->logManager->setAllLogsToReaded();
            return $this->redirectToRoute('app_dashboard');
        }

        $this->logManager->updateLogStatusById($id, 'READED');
        return $this->redirectToRoute('app_manager_logs');
    }
}
