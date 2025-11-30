<?php

namespace App\Controller\Component;

use Exception;
use App\Util\ServerUtil;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DashboardController
 *
 * Controller for dashboard component
 *
 * @package App\Controller
 */
class DashboardController extends AbstractController
{
    private ServerUtil $serverUtil;
    private LogManager $logManager;
    private BanManager $banManager;
    private UserManager $userManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;

    public function __construct(
        ServerUtil $serverUtil,
        LogManager $logManager,
        BanManager $banManager,
        UserManager $userManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager
    ) {
        $this->serverUtil = $serverUtil;
        $this->logManager = $logManager;
        $this->banManager = $banManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Render dashboard page
     *
     * @return Response The dashboard page view
     */
    #[Route('/dashboard', methods: ['GET'], name: 'app_dashboard')]
    public function dashboard(): Response
    {
        try {
            // get data for warning card
            $antiLogStatus = $this->logManager->isAntiLogEnabled();
            $diagnosticData = $this->serverUtil->getDiagnosticData();

            // get exception files (for view in warnings card)
            $exceptionFiles = $this->logManager->getExceptionFiles();

            // get system info data
            $ramUsage = $this->serverUtil->getRamUsage();
            $hostUptime = $this->serverUtil->getHostUptime();
            $storageUsage = $this->serverUtil->getStorageUsage();
            $hostSystemInfo = $this->serverUtil->getSystemInfo();
            $systemInstallInfo = $this->serverUtil->getSystemInstallInfo();

            // get host server public ip address
            $hostServerPublicIP = $this->serverUtil->getPublicIP() ?? 'N/A';

            // get running process list
            $processList = $this->serverUtil->getProcessList();

            // get services list for monitoring card
            $services = $this->serviceManager->getServicesList();

            // get logs counters
            $authLogsCount = $this->logManager->getAuthLogsCount();
            $allLogsCount = $this->logManager->getLogsCountWhereStatus();
            $readedLogsCount = $this->logManager->getLogsCountWhereStatus('READED');
            $unreadedLogsCount = $this->logManager->getLogsCountWhereStatus('UNREADED');

            // get user stats counters
            $onlineUsersCount = count($this->authManager->getOnlineUsersList());
            $bannedUsersCount = $this->banManager->getBannedCount();
            $usersCount = $this->userManager->getUsersCount();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get dashboard data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return dashboard page view
        return $this->render('component/dashboard/dashboard.twig', [
            // warning data
            'antiLogStatus' => $antiLogStatus,
            'diagnosticData' => $diagnosticData,
            'exceptionFiles' => $exceptionFiles,

            // host system info
            'ramUsage' => $ramUsage,
            'hostUptime' => $hostUptime,
            'storageUsage' => $storageUsage,
            'hostSystemInfo' => $hostSystemInfo,
            'systemInstallInfo' => $systemInstallInfo,
            'hostServerPublicIP' => $hostServerPublicIP,

            // process list
            'processList' => $processList,

            // service manager
            'services' => $services,
            'serviceManager' => $this->serviceManager,

            // logs counters
            'allLogsCount' => $allLogsCount,
            'authLogsCount' => $authLogsCount,
            'readedLogsCount' => $readedLogsCount,
            'unreadedLogsCount' => $unreadedLogsCount,

            // users stats counters
            'usersCount' => $usersCount,
            'onlineUsersCount' => $onlineUsersCount,
            'bannedUsersCount' => $bannedUsersCount
        ]);
    }
}
