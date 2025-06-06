<?php

namespace App\Controller\Component;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MetricsDashboardController
 *
 * Controller for metrics dashboard component
 *
 * @package App\Controller\Component
 */
class MetricsDashboardController extends AbstractController
{
    private AppUtil $appUtil;
    private ServerUtil $serverUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;

    public function __construct(
        AppUtil $appUtil,
        ServerUtil $serverUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        MetricsManager $metricsManager
    ) {
        $this->appUtil = $appUtil;
        $this->serverUtil = $serverUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->metricsManager = $metricsManager;
    }

    /**
     * Render metrics dashboard page
     *
     * @param Request $request The request object
     *
     * @return Response The metrics dashboard view
     */
    #[Route('/metrics/dashboard', methods:['GET'], name: 'app_metrics_dashboard')]
    public function metricsDashboard(Request $request): Response
    {
        // get metrics time period from request parameter
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');
        $showRawMetrics = $timePeriod === 'raw_metrics';

        // get metrics save interval
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL');

        try {
            // get metrics data
            if ($showRawMetrics) {
                // get raw metrics from cache
                $data = $this->metricsManager->getRawMetricsFromCache('host-system');
            } else {
                // get metrics history from database
                $data = $this->metricsManager->getServiceMetrics('host-system', $timePeriod);
            }

            // get current usages
            $currentCpuUsage = $this->serverUtil->getCpuUsage();
            $currentRamUsage = $this->serverUtil->getRamUsagePercentage();
            $currentStorageUsage = $this->serverUtil->getDriveUsagePercentage();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get metrics data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return metrics dashboard view
        return $this->render('component/metrics/metrics-dashboard.twig', [
            'metricsSaveInterval' => $metricsSaveInterval,
            'showRawMetrics' => $showRawMetrics,
            'current_usages' => [
                'cpu' => $currentCpuUsage,
                'ram' => $currentRamUsage,
                'storage' => $currentStorageUsage
            ],
            'data' => $data
        ]);
    }

    /**
     * Render service metrics page for specific service
     *
     * @param Request $request The request object
     *
     * @return Response The service metrics view
     */
    #[Route('/metrics/service', methods:['GET'], name: 'app_metrics_service')]
    public function serviceMetrics(Request $request): Response
    {
        // get request parameters
        $serviceName = (string) $request->query->get('service_name', 'host-system');
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

        // get metrics save interval
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL');

        // get metrics data
        try {
            // get metrics history from database
            $data = $this->metricsManager->getServiceMetrics($serviceName, $timePeriod);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get metrics data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return service metrics view
        return $this->render('component/metrics/service-metrics.twig', [
            'metricsSaveInterval' => $metricsSaveInterval,
            'serviceName' => $serviceName,
            'data' => $data
        ]);
    }

    /**
     * Render service metrics page for all services
     *
     * @param Request $request The request object
     *
     * @return Response The services metrics view
     */
    #[Route('/metrics/service/all', methods:['GET'], name: 'app_metrics_services_all')]
    public function serviceMetricsAll(Request $request): Response
    {
        // get time period
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

        // get metrics save interval
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL');

        // get metrics data
        try {
            // get all services metrics
            $data = $this->metricsManager->getAllServicesMetrics($timePeriod);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get metrics data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return service metrics view
        return $this->render('component/metrics/service-metrics.twig', [
            'metricsSaveInterval' => $metricsSaveInterval,
            'serviceName' => 'all-services',
            'data' => $data
        ]);
    }

    /**
     * Aggregate old metrics and redirect back to dashboard
     *
     * @return Response The redirect response
     */
    #[Route('/metrics/aggregate', methods:['GET'], name: 'app_metrics_aggregate')]
    public function aggregateMetrics(): Response
    {
        try {
            // calculate cutoff date (31 days)
            $cutoffDate = new DateTime('-31 days');

            // get aggregation preview to check if there are metrics to aggregate
            $preview = $this->metricsManager->getAggregationPreview($cutoffDate);

            if (empty($preview['old_metrics'])) {
                // no old metrics to aggregate
                $this->addFlash('info', 'No old metrics found to aggregate.');
            } else {
                // perform the aggregation
                $result = $this->metricsManager->aggregateOldMetrics($cutoffDate);

                // log event to database
                $this->logManager->log(
                    name: 'metrics-aggregation-web',
                    message: sprintf('Web-triggered aggregation: %d old metrics into %d monthly averages', $result['deleted'], $result['created']),
                    level: LogManager::LEVEL_INFO
                );
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error during metrics aggregation: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // redirect back to metrics dashboard
        return $this->redirectToRoute('app_metrics_dashboard');
    }

    /**
     * Delete metrics from database
     *
     * @param Request $request The request object
     *
     * @return Response The service metrics view
     */
    #[Route('/metrics/delete', methods:['GET'], name: 'app_metrics_delete')]
    public function deleteMetrics(Request $request): Response
    {
        // get request parameters
        $metricName = (string) $request->request->get('metric_name');
        $serviceName = (string) $request->request->get('service_name');
        $referer = (string) $request->request->get('referer', 'app_metrics_dashboard');

        // check if parameters are valid
        if (empty($metricName) || empty($serviceName)) {
            $this->errorManager->handleError(
                message: 'parameters: metric_name and service_name are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // delete metric
            $this->metricsManager->deleteMetric($metricName, $serviceName);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete metric: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return back to service metrics page
        return $this->redirectToRoute($referer, [
            'service_name' => $serviceName
        ]);
    }
}
