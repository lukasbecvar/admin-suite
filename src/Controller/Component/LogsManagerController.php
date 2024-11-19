<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LogsManagerController
 *
 * Controller for log manager component
 *
 * @package App\Controller\Component
 */
class LogsManagerController extends AbstractController
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private UserManager $userManager;
    private AuthManager $authManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Render logs table page
     *
     * @param Request $request The request object
     *
     * @return Response The logs table view
     */
    #[Route('/manager/logs', methods:['GET'], name: 'app_manager_logs')]
    public function logsTable(Request $request): Response
    {
        // get current page from request query params
        $page = (int) $request->query->get('page', '1');

        // get filter from request query params
        $filter = $request->query->get('filter', 'UNREADED');

        // get user id from query param
        $userId = $request->query->get('user_id', '0');

        // get page filters
        $limitPerPage = $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        $mainDatabase = $this->appUtil->getEnvValue('DATABASE_NAME');

        // get logs data
        $isAntiLogEnabled = $this->logManager->isAntiLogEnabled();
        $logsCount = $this->logManager->getLogsCountWhereStatus($filter, (int) $userId);
        $logs = $this->logManager->getLogsWhereStatus($filter, (int) $userId, (int) $page);

        // return logs table view
        return $this->render('component/log-manager/logs-table.twig', [
            // instances for logs manager view
            'userManager' => $this->userManager,
            'authManager' => $this->authManager,
            'visitorInfoUtil' => $this->visitorInfoUtil,

            // database name
            'mainDatabase' => $mainDatabase,

            // logs data
            'logs' => $logs,
            'logsCount' => $logsCount,

            // anti log data
            'antiLogEnabled' =>  $isAntiLogEnabled,

            // filter helpers
            'filter' => $filter,
            'userId' => $userId,
            'currentPage' => (int) $page,
            'limitPerPage' => $limitPerPage
        ]);
    }

    /**
     * Render system logs list page
     *
     * @return Response The system log view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/system', methods:['GET'], name: 'app_manager_logs_system')]
    public function systemLogsTable(): Response
    {
        // get log files from host system
        $logFiles = $this->logManager->getSystemLogs();

        // render system logs list view
        return $this->render('component/log-manager/system-logs.twig', [
            'logFiles' => $logFiles
        ]);
    }

    /**
     * Render exception files list page
     *
     * @return Response The exception log view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/exception/files', methods:['GET'], name: 'app_manager_logs_exception_files')]
    public function exceptionFiles(): Response
    {
        // get exception files
        $exceptionFiles = $this->logManager->getExceptionFiles();

        // render exception files list view
        return $this->render('component/log-manager/exception-files.twig', [
            'exceptionFiles' => $exceptionFiles
        ]);
    }

    /**
     * Handle exception file delete
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/exception/delete', methods:['GET'], name: 'app_manager_logs_exception_delete')]
    public function deleteExceptionFile(Request $request): Response
    {
        // get exception file name from query parameter
        $exceptionFile = (string) $request->query->get('file', 'none');

        // delete exception file
        if ($exceptionFile !== 'none') {
            $this->logManager->deleteExceptionFile($exceptionFile);
        }

        // redirect back to exception files page
        return $this->redirectToRoute('app_manager_logs_exception_files');
    }

    /**
     * Handle logs set to 'READED'
     *
     * @param Request $request The request object
     *
     * @return Response Redirects to the dashboard page after update logs status to 'READED'
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/set/readed', methods:['GET'], name: 'app_manager_logs_set_readed')]
    public function setAllLogsToReaded(Request $request): Response
    {
        // get current page from request query params
        $page = (int) $request->query->get('page', '1');

        // get filter from request query params
        $filter = $request->query->get('filter', 'UNREADED');

        // get user id from query param
        $userId = $request->query->get('user_id', '0');

        // get log id form query string
        $id = $request->get('id', 0);

        // validate and cast id to int
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

        // set all logs to readed
        if ($id == 0) {
            $this->logManager->setAllLogsToReaded();
            return $this->redirectToRoute('app_dashboard');
        }

        // set log status to readed
        $this->logManager->updateLogStatusById($id, 'READED');

        // redirect back to logs table page view
        return $this->redirectToRoute('app_manager_logs', [
            'page' => $page,
            'filter' => $filter,
            'user_id' => $userId
        ]);
    }
}
