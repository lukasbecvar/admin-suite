<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\FileSystemUtil;
use App\Util\VisitorInfoUtil;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogManager
 *
 * Manager for log management
 *
 * @package App\Manager
 */
class LogManager
{
    // log levels definitions
    public const LEVEL_CRITICAL = 1;
    public const LEVEL_WARNING = 2;
    public const LEVEL_NOTICE = 3;
    public const LEVEL_INFO = 4;

    private AppUtil $appUtil;
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private ErrorManager $errorManager;
    private LogRepository $logRepository;
    private FileSystemUtil $fileSystemUtil;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        CookieUtil $cookieUtil,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager,
        LogRepository $logRepository,
        FileSystemUtil $fileSystemUtil,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->errorManager = $errorManager;
        $this->logRepository = $logRepository;
        $this->entityManager = $entityManager;
        $this->fileSystemUtil = $fileSystemUtil;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Log message to the database
     *
     * @param string $name The log name
     * @param string $message The log message
     * @param int $level The log level
     *
     * @return void
     */
    public function log(string $name, string $message, int $level = 1): void
    {
        // check if log can be saved
        if (str_contains($message, 'Connection refused')) {
            return;
        }

        // check if anti-log is enabled
        if ($this->isAntiLogEnabled()) {
            return;
        }

        // check if database logging is enabled
        if (!$this->appUtil->isDatabaseLoggingEnabled()) {
            return;
        }

        // check required log level
        if ($level > (int) $this->appUtil->getEnvValue('LOG_LEVEL')) {
            return;
        }

        // get user data
        $ipAddress = $this->visitorInfoUtil->getIP();
        $userAgent = (string) $this->visitorInfoUtil->getUserAgent();

        // check if visitor ip address is unknown
        if ($ipAddress == null) {
            $ipAddress = 'Unknown';
        }

        // create log entity
        $log = new Log();
        $log->setName($name)
            ->setMessage($message)
            ->setStatus('UNREADED')
            ->setUserAgent($userAgent)
            ->setIpAddress($ipAddress)
            ->setTime(new DateTime())
            ->setLevel($level);

            // set user id if user logged in
            $userId = $this->sessionUtil->getSessionValue('user-identifier', 0);
        if (is_numeric($userId)) {
            $log->setUserId((int) $userId);
        } else {
            $log->setUserId(0);
        }

        try {
            // persist and flush log to database
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'log-error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Set anti-log cookie
     *
     * @return void
     */
    public function setAntiLog(): void
    {
        // log anti-log enable event
        $this->log('anti-log', 'anti-log enabled', self::LEVEL_WARNING);

        // set anti-log cookie
        $this->cookieUtil->set(
            name: 'anti-log',
            value: $this->appUtil->getEnvValue('ANTI_LOG_TOKEN'),
            expiration: time() + (60 * 60 * 24 * 7 * 365)
        );
    }

    /**
     * Unset anti-log cookie
     *
     * @return void
     */
    public function unSetAntiLog(): void
    {
        // log anti-log disable event
        $this->log('anti-log', 'anti-log disabled', self::LEVEL_WARNING);

        // unset anti-log cookie
        $this->cookieUtil->unset('anti-log');
    }

    /**
     * Check if anti-log is enabled
     *
     * @return bool True if anti-log is enabled, false otherwise
     */
    public function isAntiLogEnabled(): bool
    {
        // check if anti-log is set
        if (!$this->cookieUtil->isCookieSet('anti-log')) {
            return false;
        }

        // get anti-log token from cookie
        $cookieToken = $this->cookieUtil->get('anti-log');

        // check if anti-log token is valid
        if ($cookieToken == $this->appUtil->getEnvValue('ANTI_LOG_TOKEN')) {
            return true;
        }

        return false;
    }

    /**
     * Get count of logs based on their status
     *
     * @param string $status The status of the logs to count (default is 'all')
     * @param int $userId The user id for get all count logs
     *
     * @return int The count of logs
     */
    public function getLogsCountWhereStatus(string $status = 'all', int $userId = 0): int
    {
        // get logs count
        if ($status == 'all') {
            if ($userId != 0) {
                $count = $this->logRepository->count(['user_id' => $userId]);
            } else {
                $count = $this->logRepository->count();
            }
        } else {
            $count = $this->logRepository->count(['status' => $status]);
        }

        return $count;
    }

    /**
     * Get count of auth logs
     *
     * @return int The count of auth logs
     */
    public function getAuthLogsCount(): int
    {
        $count = $this->logRepository->count(
            ['name' => 'authenticator', 'status' => 'UNREADED']
        );

        return $count;
    }

    /**
     * Fetch logs based on their status
     *
     * @param string $status The status of the logs to retrieve (default is 'all')
     * @param int $userId The user id for get all logs
     * @param int $page The logs list page number
     *
     * @return array<mixed>|null An array of logs if found, or null if no logs are found
     */
    public function getLogsWhereStatus(string $status = 'all', int $userId = 0, int $page = 1): ?array
    {
        // get page limitter
        $perPage = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // calculate offset
        $offset = ($page - 1) * $perPage;

        // get logs list
        if ($status == 'all') {
            if ($userId != 0) {
                $logs = $this->logRepository->findBy(['user_id' => $userId], null, $perPage, $offset);
            } else {
                $logs = $this->logRepository->findBy([], null, $perPage, $offset);
            }
        } else {
            $logs = $this->logRepository->findBy(['status' => $status], ['id' => 'DESC'], $perPage, $offset);
        }

        // log logs viewed event
        $this->log('log-manager', strtolower($status) . ' logs viewed', self::LEVEL_INFO);

        return $logs;
    }

    /**
     * Get monitoring logs
     *
     * @param int $limit The limit of logs to get
     *
     * @return array<mixed>|null The monitoring logs
     */
    public function getMonitoringLogs(int $limit): ?array
    {
        return $this->logRepository->findBy(['name' => 'monitoring'], ['id' => 'DESC'], $limit);
    }

    /**
     * Update status of a log by ID
     *
     * @param int $id The ID of the log entry to update
     * @param string $newStatus The new status to set for the log entry
     *
     * @return void
     */
    public function updateLogStatusById(int $id, string $newStatus): void
    {
        /** @var \App\Entity\Log $log */
        $log = $this->logRepository->find($id);

        // check if log found in database
        if (!$log) {
            $this->errorManager->handleError(
                message: 'log status update error: log id: ' . $id . ' not found',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // update status
        try {
            $log->setStatus($newStatus);

            // flush data to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update log status: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log action
        $this->log(
            name: 'log-manager',
            message: 'Log: ' . $log->getId() . ' status was updated to: ' . $newStatus,
            level: self::LEVEL_INFO
        );
    }

    /**
     * Set all logs with status 'UNREADED' to 'READED'
     *
     * @return void
     */
    public function setAllLogsToReaded(): void
    {
        /** @var array<Log> $logs */
        $logs = $this->logRepository->findBy(['status' => 'UNREADED']);

        if (is_iterable($logs)) {
            // set all logs to readed status
            foreach ($logs as $log) {
                $log->setStatus('READED');
            }
        }

        // flush changes to the database
        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to set all logs status to "READED": ' . $e,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get list of system log files
     *
     * @return array<array<mixed>> Array of relative pathnames of log files found in logs directory
     */
    public function getSystemLogs(): array
    {
        // get system logs directory
        $systemLogsDirectory = $this->appUtil->getEnvValue('SYSTEM_LOGS_DIR');

        // get logs files list
        $logFiles = $this->fileSystemUtil->getFilesList($systemLogsDirectory, true);

        // log system logs viewed event
        $this->log('log-manager', 'system logs viewed', self::LEVEL_NOTICE);

        // return log files
        return $logFiles;
    }

    /**
     * Get content of specific system log file
     *
     * @param string $logFile The relative pathname of the log file to retrieve
     *
     * @return mixed The content of the log file, or null if the file does not exist
     */
    public function getSystemLogContent(string $logFile): mixed
    {
        $log = null;

        // get log file content
        $log = $this->fileSystemUtil->getFileContent($logFile);

        // log system log content viewed event
        $this->log(
            name: 'log-manager',
            message: 'system log: ' . $logFile . ' viewed',
            level: self::LEVEL_NOTICE
        );

        // return log content
        return $log;
    }

    /**
     * Get ssh logins from journalctl
     *
     * @return array<mixed>|null The ssh logins from journalctl
     */
    public function getSshLoginsFromJournalctl(): array|null
    {
        // build log get command
        $cmd = 'sudo journalctl -u ssh --no-pager';

        // execute command
        try {
            $output = shell_exec($cmd);
            if (!$output) {
                return [];
            }

            // format output to array
            $lines = explode("\n", $output);
            $logins = [];
            foreach ($lines as $line) {
                if (str_contains($line, 'Accepted')) {
                    if (preg_match('/^(\w+\s+\d+\s+\d+:\d+:\d+)\s+(.*?)\s+sshd\[\d+\]:\s+Accepted\s+(\w+)\s+for\s+([\w\-]+)\s+from\s+([\d\.]+)\s+port\s+(\d+)/', $line, $matches)) {
                        $logins[] = [
                            'date'     => $matches[1],
                            'host'     => $matches[2],
                            'method'   => $matches[3],
                            'user'     => $matches[4],
                            'ip'       => $matches[5],
                            'port'     => $matches[6],
                        ];
                    }
                }
            }
            return array_reverse($logins);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get ssh logins from journalctl: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get exception files list
     *
     * @return array<mixed>|null
     */
    public function getExceptionFiles(): ?array
    {
        $files = [];

        try {
            /** @var array<string,array<string,string>> $exceptionFiles list of exception files */
            $exceptionFiles = $this->appUtil->loadConfig('exceptions-monitoring.json');

            // check if exception files config is array
            if (!is_array($exceptionFiles)) {
                $this->errorManager->handleError(
                    message: 'error to get exception files: exception files config is not an array',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // check if exception files exist and add them to the files array
            foreach ($exceptionFiles as $exceptionFile) {
                if (file_exists($exceptionFile['path'])) {
                    $files[$exceptionFile['name']] = $exceptionFile;
                }
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get exception files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $files;
    }

    /**
     * Delete exception file
     *
     * @param string $exceptionFile The name of the exception file to delete
     *
     * @return void
     */
    public function deleteExceptionFile(string $exceptionFile): void
    {
        /** @var array<string,array<string,string>> $exceptionFiles list of exception files */
        $exceptionFiles = $this->getExceptionFiles();

        try {
            // check if exception file exists
            if (isset($exceptionFiles[$exceptionFile])) {
                $exceptionFile = $exceptionFiles[$exceptionFile]['path'];

                // delete exception file
                if (file_exists($exceptionFile)) {
                    // delete exception file
                    $this->fileSystemUtil->deleteFileOrDirectory($exceptionFile);

                    // log exception file deleted event
                    $this->log(
                        name: 'log-manager',
                        message: 'exception file deleted: ' . $exceptionFile,
                        level: self::LEVEL_CRITICAL
                    );
                }
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete exception file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
