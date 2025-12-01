<?php

namespace App\Controller\Component;

use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use App\Manager\DatabaseManager;
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
    private ErrorManager $errorManager;
    private DatabaseManager $databaseManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        DatabaseManager $databaseManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->databaseManager = $databaseManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Render logs table page
     *
     * @param Request $request The request object
     *
     * @return Response The logs table view
     */
    #[Route('/manager/logs', methods: ['GET'], name: 'app_manager_logs')]
    public function logsTable(Request $request): Response
    {
        // get filter parameters from request query
        $page = (int) $request->query->get('page', '1');
        $userId = $request->query->get('user_id', '0');
        $filter = $request->query->get('filter', 'UNREADED');

        try {
            // get filter for pagination
            $limitPerPage = $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
            $mainDatabase = $this->appUtil->getEnvValue('DATABASE_NAME');

            // get logs data
            $isAntiLogEnabled = $this->logManager->isAntiLogEnabled();
            $logsCount = $this->logManager->getLogsCountWhereStatus($filter, (int) $userId);
            $logs = $this->logManager->getLogsWhereStatus($filter, (int) $userId, (int) $page);

            // get database name of log entity
            $logsTableName = $this->databaseManager->getEntityTableName(Log::class);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return logs table view
        return $this->render('component/log-manager/logs-table.twig', [
            // instances for logs manager view
            'userManager' => $this->userManager,
            'authManager' => $this->authManager,
            'visitorInfoUtil' => $this->visitorInfoUtil,

            // database data
            'mainDatabase' => $mainDatabase,
            'logsTableName' => $logsTableName,

            // logs data
            'logs' => $logs,
            'logsCount' => $logsCount,

            // anti log status
            'antiLogEnabled' =>  $isAntiLogEnabled,

            // filter parameters
            'filter' => $filter,
            'userId' => $userId,
            'currentPage' => (int) $page,
            'limitPerPage' => $limitPerPage
        ]);
    }

    /**
     * Handle set all logs to 'READED' status
     *
     * @param Request $request The request object
     *
     * @return Response Redirects to dashboard page after update logs status to 'READED'
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/set/readed', methods: ['POST'], name: 'app_manager_logs_set_readed')]
    public function setAllLogsToReaded(Request $request): Response
    {
        // get query parameters from request
        $id = (int) $request->request->get('id', '0');
        $page = (int) $request->request->get('page', '1');
        $userId = $request->request->get('user_id', '0');
        $filter = $request->request->get('filter', 'UNREADED');

        // action for all logs
        if ($id == 0) {
            // set all logs to readed
            $this->logManager->setAllLogsToReaded();

            // redirect back to logs table page
            return $this->redirectToRoute('app_dashboard');
        }

        // set log status to readed for specific log
        try {
            $this->logManager->updateLogStatusById($id, 'READED');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to set log status: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect back to logs table page
        return $this->redirectToRoute('app_manager_logs', [
            'page' => $page,
            'filter' => $filter,
            'user_id' => $userId
        ]);
    }

    /**
     * Render system logs list page
     *
     * @return Response The system logs view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/system', methods: ['GET'], name: 'app_manager_logs_system')]
    public function systemLogsTable(): Response
    {
        // get log files from host system
        try {
            $logFiles = $this->logManager->getSystemLogs();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get system logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

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
    #[Route('/manager/logs/exception/files', methods: ['GET'], name: 'app_manager_logs_exception_files')]
    public function exceptionFiles(): Response
    {
        // get exception files
        try {
            $exceptionFiles = $this->logManager->getExceptionFiles();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get exception files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // render exception files list view
        return $this->render('component/log-manager/exception-files.twig', [
            'exceptionFiles' => $exceptionFiles
        ]);
    }

    /**
     * Handle exception-log file delete
     *
     * @param Request $request The request object
     *
     * @return Response The redirect response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/logs/exception/delete', methods: ['POST'], name: 'app_manager_logs_exception_delete')]
    public function deleteExceptionFile(Request $request): Response
    {
        // get exception file name from query parameter
        $exceptionFile = (string) $request->request->get('file', 'none');

        try {
            // delete exception file
            if ($exceptionFile !== 'none') {
                $this->logManager->deleteExceptionFile($exceptionFile);
            }
            // get exception files list
            $exceptionFiles = $this->logManager->getExceptionFiles();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete exception file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect to dashboard if exception files list is empty after deleting last exception file
        if (empty($exceptionFiles) || $exceptionFiles == null) {
            return $this->redirectToRoute('app_dashboard');
        }

        // redirect back to exception files page
        return $this->redirectToRoute('app_manager_logs_exception_files');
    }
}
