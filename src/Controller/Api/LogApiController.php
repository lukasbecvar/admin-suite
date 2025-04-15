<?php

namespace App\Controller\Api;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
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
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, CacheUtil $cacheUtil, LogManager $logManager, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle log from external service
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
                'status' => 'error',
                'message' => 'Parameter "token" is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // get api token for authentication
        $apiToken = $this->appUtil->getEnvValue('EXTERNAL_API_LOG_TOKEN');

        // check is token matches with auth token
        if ($accessToken != $apiToken) {
            return $this->json([
                'status' => 'error',
                'message' => 'Access token is invalid'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // get log data from request parameters
        $name = (string) $request->query->get('name');
        $message = (string) $request->query->get('message');
        $level = (int) $request->query->get('level');

        // check parameters are set
        if (empty($name) || empty($message) || empty($level)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Parameters name, message and level are required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // save log to database
            $this->logManager->log($name, $message, $level);

            // return success message
            return $this->json([
                'status' => 'success',
                'message' => 'Log message has been logged'
            ], JsonResponse::HTTP_OK);
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: $message,
                code: JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error response
            return $this->json([
                'status' => 'error',
                'message' => 'Error to log message'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get system logs with journalctl command execute
     *
     * @return JsonResponse The JSON response with system logs
     */
    #[Route('/api/system/logs', methods:['GET'], name: 'app_api_system_logs')]
    public function getSystemLogs(): JsonResponse
    {
        // cache key to save last get time
        $cacheKey = 'last_journalctl_timestamp';

        // get last get time
        $cacheItem = $this->cacheUtil->getValue($cacheKey);
        if ($cacheItem != null) {
            $lastTimestamp = $cacheItem->get();
        } else {
            $lastTimestamp = (new DateTime('-10 seconds'))->format('Y-m-d H:i:s');
        }

        // journalctl command execute to get logs
        try {
            $escapedSince = escapeshellarg("'" . $lastTimestamp . "'");
            $command = "sudo journalctl --since $escapedSince -o short-iso";
            $output = shell_exec($command);
            if ($output == false) {
                $output = 'No logs found.';
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'Error to get system logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // update timestamp in cache for next call
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $this->cacheUtil->deleteValue($cacheKey);
        $this->cacheUtil->setValue($cacheKey, $now, (60 * 60 * 24));

        return $this->json([
            'from' => $lastTimestamp,
            'to' => $now,
            'logs' => explode("\n", trim($output)),
        ]);
    }
}
