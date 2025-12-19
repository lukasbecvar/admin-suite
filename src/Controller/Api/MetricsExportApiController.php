<?php

namespace App\Controller\Api;

use Throwable;
use App\Util\XmlUtil;
use DateTimeImmutable;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MetricsExportApiController
 *
 * Exports metrics data for consumption in external tools
 *
 * @package App\Controller\Api
 */
class MetricsExportApiController extends AbstractController
{
    private const SUPPORTED_PERIODS = [
        'last_24_hours',
        'last_week',
        'last_month',
        'all_time',
    ];

    private XmlUtil $xmlUtil;
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;

    public function __construct(XmlUtil $xmlUtil, ErrorManager $errorManager, MetricsManager $metricsManager)
    {
        $this->xmlUtil = $xmlUtil;
        $this->errorManager = $errorManager;
        $this->metricsManager = $metricsManager;
    }

    /**
     * API endpoint to export metrics data as JSON or XML
     *
     * Query parameters:
     * - service_name (string, optional): Service identifier, defaults to host-system
     * - time_period (string, optional): One of last_24_hours, last_week, last_month, all_time
     * - format (string, optional): json (default) or xml
     *
     * @param Request $request The current request
     *
     * @return Response Metrics data formatted to JSON or XML
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/api/metrics/export', methods: ['GET'], name: 'api_metrics_export')]
    public function export(Request $request): Response
    {
        $serviceName = (string) $request->query->get('service_name', 'host-system');
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');
        $format = strtolower((string) $request->query->get('format', 'json'));

        // validate time period
        if (!in_array($timePeriod, self::SUPPORTED_PERIODS, true)) {
            $this->errorManager->handleError(
                message: 'invalid time period, supported values: ' . implode(', ', self::SUPPORTED_PERIODS),
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $metrics = $this->metricsManager->getServiceMetrics($serviceName, $timePeriod);
        } catch (Throwable $exception) {
            $this->errorManager->handleError(
                message: 'error to export metrics data: ' . $exception->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // build payload
        $payload = [
            'service' => $serviceName,
            'time_period' => $timePeriod,
            'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'data' => $metrics,
        ];

        // return data in xml format
        if ($format === 'xml') {
            $xmlContent = $this->xmlUtil->formatToXml($payload, 'metrics');
            return new Response($xmlContent, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
        }

        return $this->json($payload, JsonResponse::HTTP_OK);
    }
}
