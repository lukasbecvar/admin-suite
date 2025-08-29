<?php

namespace App\Controller\Api;

use Exception;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ServiceVisitorTrackingApiController
 *
 * Controller for external service visitor tracking API
 *
 * @package App\Controller\Api
 */
class ServiceVisitorTrackingApiController extends AbstractController
{
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;
    private MetricsManager $metricsManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(
        ErrorManager $errorManager,
        ServiceManager $serviceManager,
        MetricsManager $metricsManager,
        VisitorInfoUtil $visitorInfoUtil
    ) {
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
        $this->metricsManager = $metricsManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Handle external service visitor tracking
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response with status
     */
    #[Route('/api/monitoring/visitor/tracking', methods:['POST'], name: 'app_api_monitoring_visitor_tracking')]
    public function visitorTracking(Request $request): JsonResponse
    {
        // get service name
        $serviceName = (string) $request->get('service_name');

        // check if service name is set
        if (empty($serviceName)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Parameter "service_name" is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // get service config
        $serviceConfig = $this->serviceManager->getServicesList();

        // check if service config is loaded
        if ($serviceConfig === null) {
            return $this->json([
                'status' => 'error',
                'message' => 'Service config is not loaded'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // check if service config found
        if (!array_key_exists($serviceName, $serviceConfig)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Service not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // check if service is web http type
        if ($serviceConfig[$serviceName]['type'] != 'http') {
            return $this->json([
                'status' => 'error',
                'message' => 'Service is not web http type'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check if service config is set
        if ($serviceConfig == null) {
            return $this->json([
                'status' => 'error',
                'message' => 'Unknown service'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // get request uri
        $requestUri = $request->getUri();

        // check if request uri is allowed
        if (!str_contains($serviceConfig[$serviceName]['url'], $requestUri)) {
            $this->errorManager->handleError(
                message: 'error to init visitor tracking: request uri is not allowed',
                code: Response::HTTP_FORBIDDEN
            );
        }

        // get visitor ip address
        $ipAddress = $this->visitorInfoUtil->getIP();

        // check if ip address is detected
        if ($ipAddress === null) {
            return $this->json([
                'status' => 'error',
                'message' => 'Visitor ip cannot be detected'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // get visitor user agent
        $userAgent = $this->visitorInfoUtil->getUserAgent();

        // check if user agent is valid
        if ($userAgent === null) {
            $userAgent = 'Unknown';
        }

        // get visitor referer
        $referer = $this->visitorInfoUtil->getReferer();

        // check if visitor is already registered
        if ($this->metricsManager->checkIfVisitorAlreadyRegistered($ipAddress, $serviceName)) {
            try {
                // update visitor data
                $this->metricsManager->updateServiceVisitorLastVisitTime($ipAddress, $serviceName);
                $this->metricsManager->updateServiceVisitorUserAgent($ipAddress, $serviceName, $userAgent);
                $this->metricsManager->updateServiceVisitorReferer($ipAddress, $serviceName, $referer);

                // return success response
                return $this->json([
                    'status' => 'success',
                    'message' => 'Visitor data updated'
                ], JsonResponse::HTTP_OK);
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to update service visitor data: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // get ip info
        $ipInfo = (array) $this->visitorInfoUtil->getIpInfo($ipAddress);

        // check if ip info is valid
        if (
            !empty($ipInfo['status']) && $ipInfo['status'] === 'success' &&
            !empty($ipInfo['countryCode']) && !empty($ipInfo['city'])
        ) {
            $location = $ipInfo['countryCode'] . '/' . $ipInfo['city'];
        } else {
            $location = 'Unknown';
        }

        try {
            // register service visitor
            $this->metricsManager->registerServiceVisitor(
                serviceName: $serviceName,
                ipAddress: $ipAddress,
                location: $location,
                referer: $referer,
                userAgent: $userAgent
            );

            // return success response
            return $this->json([
                'status' => 'success',
                'message' => 'Visitor registered'
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to register service visitor: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
