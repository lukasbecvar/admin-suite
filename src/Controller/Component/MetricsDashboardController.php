<?php

namespace App\Controller\Component;

use Exception;
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
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;

    public function __construct(ErrorManager $errorManager, MetricsManager $metricsManager)
    {
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
        // get metrics time period
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

        // get metrics data
        $data = $this->metricsManager->getResourceUsageMetrics($timePeriod);

        // return metrics dashboard view
        return $this->render('component/metrics-dashboard/metrics-dashboard.twig', [
            'data' => $data
        ]);
    }

    /**
     * Render service metrics page
     *
     * @param Request $request The request object
     *
     * @return Response The service metrics view
     */
    #[Route('/metrics/service', methods:['GET'], name: 'app_metrics_service')]
    public function serviceMetrics(Request $request): Response
    {
        // get metrics time period
        $serviceName = (string) $request->query->get('service_name', 'host-system');
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

        // get metrics data
        $data = $this->metricsManager->getServiceMetrics($serviceName, $timePeriod);

        // return service metrics view
        return $this->render('component/metrics-dashboard/service-metrics.twig', [
            'serviceName' => $serviceName,
            'data' => $data
        ]);
    }

    /**
     * Render service metrics page for all services
     *
     * @param Request $request The request object
     *
     * @return Response The service metrics view
     */
    #[Route('/metrics/service/all', methods:['GET'], name: 'app_metrics_services_all')]
    public function serviceMetricsAll(Request $request): Response
    {
        // get time period
        $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

        // get metrics data
        $data = $this->metricsManager->getAllServicesMetrics($timePeriod);

        // return service metrics view
        return $this->render('component/metrics-dashboard/service-metrics.twig', [
            'serviceName' => 'all-services',
            'data' => $data
        ]);
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
        // get metric name and service name
        $metricName = (string) $request->request->get('metric_name');
        $serviceName = (string) $request->request->get('service_name');
        $confirm = (string) $request->request->get('confirm', 'none');

        // check if parameters are valid
        if (empty($metricName) || empty($serviceName)) {
            $this->errorManager->handleError(
                message: 'Parameters: metric_name and service_name are required',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        if ($confirm == 'none') {
            return $this->render('component/metrics-dashboard/delete-metric-confirmation.twig', [
                'metricName' => $metricName,
                'serviceName' => $serviceName
            ]);
        }

        // check if user confirmed the action
        if ($confirm == 'no') {
            return $this->redirectToRoute('app_metrics_service', [
                'service_name' => $serviceName
            ]);
        }

        // delete metric
        try {
            $this->metricsManager->deleteMetric($metricName, $serviceName);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error deleting metric: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return back to service metrics page
        return $this->redirectToRoute('app_metrics_service', [
            'service_name' => $serviceName
        ]);
    }
}
