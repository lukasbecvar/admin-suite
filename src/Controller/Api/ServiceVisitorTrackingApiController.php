<?php

namespace App\Controller\Api;

use Exception;
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
    private ServiceManager $serviceManager;
    private MetricsManager $metricsManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(ServiceManager $serviceManager, MetricsManager $metricsManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->serviceManager = $serviceManager;
        $this->metricsManager = $metricsManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * CORS preflight request
     *
     * @return Response The response object
     */
    #[Route('/api/monitoring/visitor/tracking', methods: ['OPTIONS'])]
    public function preflight(): Response
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_NO_CONTENT);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        return $response;
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
        $response = new JsonResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');

        // get request data
        $data = json_decode($request->getContent(), true);

        // get service name
        $serviceName = $data['service_name'] ?? null;

        // check if service name is set
        if (empty($serviceName)) {
            $response->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Parameter "service_name" is required'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // get service config
        $serviceConfig = $this->serviceManager->getServicesList();

        // check if service config is loaded
        if ($serviceConfig === null) {
            $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Service config is not loaded'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // check if service config found
        if (!array_key_exists($serviceName, $serviceConfig)) {
            $response->setStatusCode(JsonResponse::HTTP_NOT_FOUND);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Service not found'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // check if service is web http type
        if ($serviceConfig[$serviceName]['type'] != 'http') {
            $response->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Service is not web http type'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // check if service config is set
        if ($serviceConfig == null) {
            $response->setStatusCode(JsonResponse::HTTP_NOT_FOUND);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Unknown service'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // get request origin
        $requestOrigin = $request->headers->get('Origin');

        // check if request origin is set
        if (empty($requestOrigin)) {
            $response->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Request header origin is required'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // check if request origin is allowed
        if (!str_contains($serviceConfig[$serviceName]['url'], $requestOrigin)) {
            $response->setStatusCode(JsonResponse::HTTP_FORBIDDEN);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Request origin is not allowed'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // get visitor ip address
        $ipAddress = $this->visitorInfoUtil->getIP();

        // check if ip address is detected
        if ($ipAddress === null) {
            $response->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Visitor ip cannot be detected'
            ], JSON_THROW_ON_ERROR));
            return $response;
        }

        // get visitor user agent
        $userAgent = $this->visitorInfoUtil->getUserAgent();

        // check if user agent is valid
        if ($userAgent === null) {
            $userAgent = 'Unknown';
        }

        // get visitor referer
        $referer = $data['referer'] ?? 'Unknown';

        // check if visitor is already registered
        if ($this->metricsManager->checkIfVisitorAlreadyRegistered($ipAddress, $serviceName)) {
            try {
                // update visitor data
                $this->metricsManager->updateServiceVisitorLastVisitTime($ipAddress, $serviceName);
                $this->metricsManager->updateServiceVisitorUserAgent($ipAddress, $serviceName, $userAgent);
                $this->metricsManager->updateServiceVisitorReferer($ipAddress, $serviceName, $referer);

                // return success response
                $response->setStatusCode(JsonResponse::HTTP_OK);
                $response->setContent((string) json_encode([
                    'status' => 'success',
                    'message' => 'Visitor data updated'
                ], JSON_THROW_ON_ERROR));
                return $response;
            } catch (Exception $e) {
                $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                $response->setContent((string) json_encode([
                    'status' => 'error',
                    'message' => 'Error to update service visitor data: ' . $e->getMessage()
                ], JSON_THROW_ON_ERROR));
                return $response;
            }
        }

        // get ip info
        $ipInfo = (array) $this->visitorInfoUtil->getIpInfo($ipAddress);

        // check if ip info is valid
        if (!empty($ipInfo['status']) && $ipInfo['status'] === 'success' && !empty($ipInfo['countryCode']) && !empty($ipInfo['city'])) {
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
            $response->setStatusCode(JsonResponse::HTTP_OK);
            $response->setContent((string) json_encode([
                'status' => 'success',
                'message' => 'Visitor registered'
            ], JSON_THROW_ON_ERROR));
            return $response;
        } catch (Exception $e) {
            $response->setStatusCode(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            $response->setContent((string) json_encode([
                'status' => 'error',
                'message' => 'Error to register service visitor: ' . $e->getMessage()
            ], JSON_THROW_ON_ERROR));
            return $response;
        }
    }
}
