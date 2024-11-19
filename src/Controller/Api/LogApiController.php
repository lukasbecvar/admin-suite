<?php

namespace App\Controller\Api;

use App\Util\AppUtil;
use App\Manager\LogManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LogApiController
 *
 * Controller for external log API
 *
 * @package App\Controller\Api
 */
class LogApiController extends AbstractController
{
    private AppUtil $appUtil;
    private LogManager $logManager;

    public function __construct(AppUtil $appUtil, LogManager $logManager)
    {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
    }

    /**
     * Handle the external log API
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response with status
     */
    #[Route('/api/external/log', methods:['POST'], name: 'app_api_external_log')]
    public function externalLog(Request $request): JsonResponse
    {
        // get access token from request parameter
        $accessToken = (string) $request->query->get('token');

        // check if token is set
        if (empty($accessToken)) {
            return $this->json([
                'error' => 'Access token is not set'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // get api token for authentication
        $apiToken = $this->appUtil->getEnvValue('EXTERNAL_API_LOG_TOKEN');

        // check is token matches with auth token
        if ($accessToken != $apiToken) {
            return $this->json([
                'error' => 'Access token is invalid'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // get log data from request parameters
        $name = (string) $request->query->get('name');
        $message = (string) $request->query->get('message');
        $level = (int) $request->query->get('level');

        // check parameters are set
        if (empty($name) || empty($message) || empty($level)) {
            return $this->json([
                'error' => 'Parameters name, message and level are required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // save log to database
        $this->logManager->log($name, $message, $level);

        // return success message
        return $this->json([
            'success' => 'Log message has been logged'
        ], JsonResponse::HTTP_OK);
    }
}
