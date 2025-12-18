<?php

namespace App\Controller\Api;

use DateTime;
use Exception;
use App\Util\XmlUtil;
use App\Util\CacheUtil;
use App\Util\SessionUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Annotation\CsrfProtection;
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
    private XmlUtil $xmlUtil;
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private SessionUtil $sessionUtil;
    private ErrorManager $errorManager;

    public function __construct(
        XmlUtil $xmlUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager
    ) {
        $this->xmlUtil = $xmlUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->sessionUtil = $sessionUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle log from external service
     *
     * This endpoint is used in external services.
     * Supports traditional query parameters or XML payloads
     * with the following structure:
     *
     * XML payload:
     *  <log>
     *    <name>string</name>
     *    <message>string</message>
     *    <level>int</level>
     *  </log>
     *
     * Request query parameters:
     *  - name: log name (string)
     *  - message: log message (string)
     *  - level: log level (int)
     *
     * @param Request $request The request object
     *
     * @return JsonResponse The JSON response with status
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/api/external/log', methods: ['POST'], name: 'app_api_external_log')]
    public function externalLog(Request $request): JsonResponse
    {
        // get log data from request
        $message = (string) $request->query->get('message');
        $name = (string) $request->query->get('name');
        $level = (int) $request->query->get('level');

        // parse XML payload if provided
        if ($this->xmlUtil->isXmlRequest($request)) {
            try {
                $xmlPayload = $this->xmlUtil->parseXmlPayload($request->getContent());
            } catch (Exception $e) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid XML payload: ' . $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (isset($xmlPayload->name) && (string) $xmlPayload->name !== '') {
                $name = (string) $xmlPayload->name;
            }
            if (isset($xmlPayload->message) && (string) $xmlPayload->message !== '') {
                $message = (string) $xmlPayload->message;
            }
            if (isset($xmlPayload->level) && (string) $xmlPayload->level !== '') {
                $level = (int) $xmlPayload->level;
            }
        }

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
                message: 'error to log external message: ' . $e->getMessage(),
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
     * Get system logs from journalctl
     *
     * This endpoint is used in system audit component
     *
     * @return JsonResponse|Response The JSON response with system logs
     */
    #[Route('/api/system/logs', methods: ['GET'], name: 'app_api_system_logs')]
    public function getSystemLogs(Request $request): JsonResponse|Response
    {
        // get session id (to store last get time separately for each session)
        $sessionId = $this->sessionUtil->getSessionId();

        // cache key to save last get time
        $cacheKey = 'last_log_get_time_' . substr(md5($sessionId), 0, 8);

        // get last get time
        $cacheItem = $this->cacheUtil->getValue($cacheKey);
        $lastTimestamp = $cacheItem->get();
        if ($lastTimestamp != null) {
            $lastTimestamp = $lastTimestamp;
        } else {
            $lastTimestamp = (new DateTime('-30 seconds'))->format('Y-m-d H:i:s');
        }

        // journalctl command execute to get logs
        try {
            $command = "sudo journalctl --since '$lastTimestamp' -o short-iso | grep -v 'COMMAND=/usr/bin/journalctl' | grep -v 'pam_unix(sudo:session)'";
            $output = shell_exec($command);
            if ($output == false) {
                $output = '';
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
        $this->cacheUtil->setValue($cacheKey, $now, 30);

        // build response payload
        $payload = [
            'from' => $lastTimestamp,
            'to' => $now,
            'logs' => explode("\n", trim($output))
        ];

        // return XML response if requested
        if (strtolower((string) $request->query->get('format')) === 'xml') {
            $xmlContent = $this->xmlUtil->formatToXml($payload, 'systemLogs');
            return new Response($xmlContent, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
        }

        // return response with logs
        return $this->json($payload, JsonResponse::HTTP_OK);
    }

    /**
     * Get SSH access history from journalctl
     *
     * This endpoint is used in system audit component
     * for lazy loading SSH access history card
     *
     * @return JsonResponse The JSON response with ssh access history
     */
    #[Route('/api/system/ssh-access-history', methods: ['GET'], name: 'app_api_system_ssh_access_history')]
    public function getSshAccessHistory(): JsonResponse
    {
        // get ssh logins from journalctl
        $sshAccessHistory = $this->logManager->getSshLoginsFromJournalctl();

        if ($sshAccessHistory === null) {
            $sshAccessHistory = [];
        }

        // build response payload
        $payload = [
            'ssh_access_history' => $sshAccessHistory,
            'count' => count($sshAccessHistory)
        ];

        // return response with ssh access history
        return $this->json($payload, JsonResponse::HTTP_OK);
    }
}
