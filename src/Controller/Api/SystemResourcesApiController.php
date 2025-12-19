<?php

namespace App\Controller\Api;

use App\Util\XmlUtil;
use App\Util\ServerUtil;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
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
    private XmlUtil $xmlUtil;
    private ServerUtil $serverUtil;

    public function __construct(XmlUtil $xmlUtil, ServerUtil $serverUtil)
    {
        $this->xmlUtil = $xmlUtil;
        $this->serverUtil = $serverUtil;
    }

    /**
     * API to get system resources data
     *
     * This endpoint is used in system dashboard
     *
     * @param Request $request The current request
     *
     * @return Response The system resources data in JSON or XML
     */
    #[Authorization(authorization: 'USER')]
    #[Route('/api/system/resources', methods: ['GET'], name: 'api_system_resources')]
    public function terminalAction(Request $request): Response
    {
        // get host uptime and diagnostic data
        $ramUsage = $this->serverUtil->getRamUsage();
        $hostUptime = $this->serverUtil->getHostUptime();
        $storageUsage = $this->serverUtil->getStorageUsage();
        $networkStats = $this->serverUtil->getNetworkStats();
        $diagnosticData = $this->serverUtil->getDiagnosticData();

        // build response payload
        $payload = [
            'ramUsage' => $ramUsage,
            'hostUptime' => $hostUptime,
            'storageUsage' => $storageUsage,
            'networkStats' => $networkStats,
            'diagnosticData' => $diagnosticData
        ];

        // return XML response if requested
        if (strtolower((string) $request->query->get('format')) === 'xml') {
            $xmlContent = $this->xmlUtil->formatToXml($payload, 'resources');
            return new Response($xmlContent, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
        }

        // return JSON response
        return $this->json($payload, JsonResponse::HTTP_OK);
    }
}
