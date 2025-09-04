<?php

namespace App\Controller\Api;

use App\Util\ServerUtil;
use App\Annotation\Authorization;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class SystemResourcesApiController
 *
 * Controller for get system resources data
 *
 * @package App\Controller\Api
 */
class SystemResourcesApiController extends AbstractController
{
    private ServerUtil $serverUtil;

    public function __construct(ServerUtil $serverUtil)
    {
        $this->serverUtil = $serverUtil;
    }

    /**
     * API to get system resources data
     * 
     * This endpoint is used in system dashboard
     *
     * @return JsonResponse The system resources data
     */
    #[Authorization(authorization: 'USER')]
    #[Route('/api/system/resources', methods: ['GET'], name: 'api_system_resources')]
    public function terminalAction(): JsonResponse
    {
        // get host uptime and diagnostic data
        $ramUsage = $this->serverUtil->getRamUsage();
        $hostUptime = $this->serverUtil->getHostUptime();
        $storageUsage = $this->serverUtil->getStorageUsage();
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // get network stats
        $networkStats = $this->serverUtil->getNetworkStats();

        return $this->json([
            'ramUsage' => $ramUsage,
            'hostUptime' => $hostUptime,
            'storageUsage' => $storageUsage,
            'networkStats' => $networkStats,
            'diagnosticData' => $diagnosticData
        ]);
    }
}
