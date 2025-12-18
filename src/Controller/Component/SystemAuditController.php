<?php

namespace App\Controller\Component;

use Exception;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class SystemAuditController
 *
 * Controller for system audit component
 *
 * @package App\Controller\Component
 */
class SystemAuditController extends AbstractController
{
    private LogManager $logManager;
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;

    public function __construct(
        LogManager $logManager,
        ServerUtil $serverUtil,
        ErrorManager $errorManager,
        ServiceManager $serviceManager
    ) {
        $this->logManager = $logManager;
        $this->serverUtil = $serverUtil;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Render system audit component dashboard page
     *
     * @return Response The system audit page view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/system/audit', methods: ['GET'], name: 'app_system_audit')]
    public function systemAuditDashboard(): Response
    {
        // get system info data
        $hostUptime = $this->serverUtil->getHostUptime();
        $processList = $this->serverUtil->getProcessList();
        $hostSystemInfo = $this->serverUtil->getSystemInfo();
        $systemInstallInfo = $this->serverUtil->getSystemInstallInfo();

        // get ufw open ports
        $ufwOpenPorts = $this->serverUtil->getUfwOpenPorts();

        // get diagnostic data
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // get linux system users
        $linuxUsers = $this->serverUtil->getLinuxUsers();

        // get log files from host system
        try {
            $logFiles = $this->logManager->getSystemLogs();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get system logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return system audit dashboard page view
        return $this->render('component/system-audit/audit-dashboard.twig', [
            // service manager instance
            'serviceManager' => $this->serviceManager,

            // system info data
            'processList' => $processList,
            'hostUptime' => $hostUptime,
            'hostSystemInfo' => $hostSystemInfo,
            'systemInstallInfo' => $systemInstallInfo,

            // log files data
            'logFiles' => $logFiles,

            // ufw open ports
            'ufwOpenPorts' => $ufwOpenPorts,

            // diagnostic data
            'diagnosticData' => $diagnosticData,

            // linux system users
            'linuxUsers' => $linuxUsers
        ]);
    }
}
