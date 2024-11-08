<?php

namespace App\Controller\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MetricsDashboardController
 *
 * Controller for the metrics dashboard component
 *
 * @package App\Controller\Component
 */
class MetricsDashboardController extends AbstractController
{
    /**
     * Handle the metrics dashboard page view
     *
     * @param Request $request The request object
     *
     * @return Response The metrics dashboard view
     */
    #[Route('/metrics/dashboard', methods:['GET'], name: 'app_metrics_dashboard')]
    public function metricsDashboard(Request $request): Response
    {
        // get metrics time period
        $timePeriod = (string) $request->query->get('time_period');

        // testing data
        $data = [
            'categories' => ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00'],
            'cpu' => [
                'data' => [10, 20, 15, 30, 25, 40, 0],
                'color' => '#28a745',
                'borderColor' => '#27ae60',
                'current' => 55
            ],
            'ram' => [
                'data' => [20, 30, 40, 50, 60, 70, 80],
                'color' => '#20c997',
                'borderColor' => '#1abc9c',
                'current' => 60
            ],
            'storage' => [
                'data' => [10, 56, 18, 20, 25, 30, 50],
                'color' => '#007bff',
                'borderColor' => '#0056b3',
                'current' => 85
            ]
        ];

        // return component view with metrics page
        return $this->render('component/metrics-dashboard/metrics-dashboard.twig', [
            'data' => $data
        ]);
    }
}
