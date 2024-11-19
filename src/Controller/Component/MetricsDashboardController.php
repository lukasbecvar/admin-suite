<?php

namespace App\Controller\Component;

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
    private MetricsManager $metricsManager;

    public function __construct(MetricsManager $metricsManager)
    {
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
        $data = $this->metricsManager->getMetrics($timePeriod);

        // return metrics dashboard view
        return $this->render('component/metrics-dashboard/metrics-dashboard.twig', [
            'data' => $data
        ]);
    }
}
