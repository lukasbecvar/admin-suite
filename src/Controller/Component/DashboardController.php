<?php

namespace App\Controller\Component;

use App\Util\ServerUtil;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Manager\AuthManager;
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
    private ServiceManager $serviceManager;

    public function __construct(
        ServerUtil $serverUtil,
        LogManager $logManager,
        BanManager $banManager,
        UserManager $userManager,
        AuthManager $authManager,
        ServiceManager $serviceManager
    ) {
        $this->serverUtil = $serverUtil;
        $this->logManager = $logManager;
        $this->banManager = $banManager;
        $this->userManager = $userManager;
        $this->authManager = $authManager;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Render dashboard page
     *
     * @return Response The dashboard page view
     */
    #[Route('/dashboard', methods:['GET'], name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // get warning data
        $antiLogStatus = $this->logManager->isAntiLogEnabled();
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // get host system info
        $ramUsage = $this->serverUtil->getRamUsage();
        $hostUptime = $this->serverUtil->getHostUptime();
        $storageUsage = $this->serverUtil->getStorageUsage();
        $hostSystemInfo = $this->serverUtil->getSystemInfo();

        // get running process list
        $processList = $this->serverUtil->getProcessList();

        // get services list
        $services = $this->serviceManager->getServicesList();

        // get logs count
        $authLogsCount = $this->logManager->getAuthLogsCount();
        $allLogsCount = $this->logManager->getLogsCountWhereStatus();
        $readedLogsCount = $this->logManager->getLogsCountWhereStatus('READED');
        $unreadedLogsCount = $this->logManager->getLogsCountWhereStatus('UNREADED');

        // get user stats count
        $onlineUsersCount = count($this->authManager->getOnlineUsersList());
        $bannedUsersCount = $this->banManager->getBannedCount();
        $usersCount = $this->userManager->getUsersCount();

        // get exception files
        $exceptionFiles = $this->logManager->getExceptionFiles();

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

            // process list
            'processList' => $processList,

            // service manager
            'services' => $services,
            'serviceManager' => $this->serviceManager,

            // logs count
            'allLogsCount' => $allLogsCount,
            'authLogsCount' => $authLogsCount,
            'readedLogsCount' => $readedLogsCount,
            'unreadedLogsCount' => $unreadedLogsCount,

            // users count
            'usersCount' => $usersCount,
            'onlineUsersCount' => $onlineUsersCount,
            'bannedUsersCount' => $bannedUsersCount
        ]);
    }
}
