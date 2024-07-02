<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;

/**
 * Class LogsManagerController
 *
 * Controller to manage or display of logs
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
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
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

        // get current page from request query params
        $page = (int) $request->query->get('page', '1');

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
            'logs' => $this->logManager->getLogsWhereStatus($filter, (int) $userId, (int) $page),

            // anti log data
            'antiLogEnabled' => $this->logManager->isAntiLogEnabled(),

            // filter helpers
            'userId' => $userId,
            'currentPage' => (int) $page,
            'limitPerPage' => $this->appUtil->getPageLimitter(),
            'filter' => $filter,
        ]);
    }

    /**
     * Renders the system logs table
     *
     * @param Request $request The request object
     *
     * @return Response The response containing the rendered template
     */
    #[Route('/manager/logs/system', methods:['GET'], name: 'app_manager_logs_system')]
    public function systemLogsTable(Request $request): Response
    {
        // check if user has admin permissions
        if (!$this->authManager->isLoggedInUserAdmin()) {
            return $this->render('component/no-permissions.twig', [
                'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
                'userData' => $this->authManager->getLoggedUserRepository(),
            ]);
        }

        // get selected log file from query parameter
        $logFile = $request->get('file', 'none');

        // directory to scan for log files
        $logDirectory = '/var/log';

        // initialize Finder
        $finder = new Finder();
        $finder->files()->in($logDirectory);

        // array to store log files
        $logFiles = [];

        // iterate over found files
        foreach ($finder as $file) {
            // check if log is not archived
            if (!str_ends_with($file->getRelativePathname(), '.xz')) {
                $logFiles[] = $file->getRelativePathname();
            }
        }

        $logContent = 'non-selected';

        // check if a log file is selected to display its content
        if ($logFile != 'none') {
            // check if file exists
            $filePath = $logDirectory . '/' . $logFile;
            if (!file_exists($filePath)) {
                $this->errorManager->handleError('error to get log file: ' . $filePath . ' not found', 404);
            }

            // get log file content
            $logContent = file_get_contents($filePath);
        }

        return $this->render('component/logs-manager/system-logs.twig', [
            'isAdmin' => $this->authManager->isLoggedInUserAdmin(),
            'userData' => $this->authManager->getLoggedUserRepository(),

            // log files list
            'logFiles' => $logFiles,

            // current log file name
            'logFile' => $logFile,

            // log file content
            'logContent' => $logContent
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

        $this->logManager->updateLogStatusById($id, 'READED');
        return $this->redirectToRoute('app_manager_logs', [
            'page' => $page,
            'filter' => $filter,
            'user_id' => $userId
        ]);
    }
}
