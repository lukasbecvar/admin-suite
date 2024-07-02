<?php

namespace App\Manager;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogManager
 *
 * The manager for logging
 *
 * @package App\Manager
 */
class LogManager
{
    private AppUtil $appUtil;
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        CookieUtil $cookieUtil,
        SessionUtil $sessionUtil,
        ErrorManager $errorManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Log a message to the database
     *
     * @param string $name The log name
     * @param string $message The log message
     * @param int $level The log level
     *
     * @throws \Exception If the log entity cannot be persisted
     *
     * @return void
     */
    public function log(string $name, string $message, int $level = 1): void
    {
        // check if log blocking is enabled
        if ($this->isAntiLogEnabled()) {
            return;
        }

        // check if database logging is enabled
        if (!$this->appUtil->isDatabaseLoggingEnabled()) {
            return;
        }

        // check required log level
        if ($level > $this->appUtil->getLogLevel()) {
            return;
        }

        // get user data
        $userAgent = (string) $this->visitorInfoUtil->getUserAgent();
        $ipAddress = $this->visitorInfoUtil->getIP();

        // check if the ip address is null
        if ($ipAddress == null) {
            $ipAddress = 'Unknown';
        }

        // init log entity
        $log = new Log();

        // set the log properties
        $log->setName($name)
            ->setMessage($message)
            ->setStatus('UNREADED')
            ->setUserAgent($userAgent)
            ->setIpAdderss($ipAddress)
            ->setTime(new \DateTime());

            // set user id if user logged in
            $userId = $this->sessionUtil->getSessionValue('user-identifier', 0);
        if (is_numeric($userId)) {
            $log->setUserId((int) $userId);
        } else {
            $log->setUserId(0);
        }

        try {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                'log-error: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Set the anti-log cookie
     *
     * @return void
     */
    public function setAntiLog(): void
    {
        // log action
        $this->log('anti-log', 'anti-log enabled');

        // set the anti-log cookie
        $this->cookieUtil->set('anti-log', $this->appUtil->getAntiLogToken(), time() + (60 * 60 * 24 * 7 * 365));
    }

    /**
     * Unset the anti-log cookie
     *
     * @return void
     */
    public function unSetAntiLog(): void
    {
        // unset the anti-log cookie
        $this->cookieUtil->unset('anti-log');

        // log action
        $this->log('anti-log', 'anti-log disabled');
    }

    /**
     * Check if anti-log is enabled
     *
     * @return bool True if anti-log is enabled, false otherwise
     */
    public function isAntiLogEnabled(): bool
    {
        // check if anti-log is seted
        if (!$this->cookieUtil->isCookieSet('anti-log')) {
            return false;
        }

        // get anti-log token from cookie
        $cookieToken = $this->cookieUtil->get('anti-log');

        // check if anti-log token is valid
        if ($cookieToken == $this->appUtil->getAntiLogToken()) {
            return true;
        }

        return false;
    }

    /**
     * Get the count of logs based on their status
     *
     * This method retrieves the count of logs from the repository based on the specified status
     * If the status is 'all', it counts all logs. Otherwise, it counts logs
     * matching the given status.
     *
     * @param string $status The status of the logs to count. Defaults to 'all'
     * @param int $userId The user id for get all count logs
     *
     * @return int The count of logs
     */
    public function getLogsCountWhereStatus(string $status = 'all', int $userId = 0): int
    {
        $repository = $this->entityManager->getRepository(Log::class);

        // get all logs or by status
        if ($status == 'all') {
            if ($userId != 0) {
                $count = $repository->count(['user_id' => $userId]);
            } else {
                $count = $repository->count();
            }
        } else {
            $count = $repository->count(['status' => $status]);
        }

        return $count;
    }

    /**
     * Fetch logs based on their status
     *
     * This method retrieves logs from the repository based on the specified status
     * If the status is 'all', it retrieves all logs. Otherwise, it fetches logs
     * matching the given status. It also logs this action.
     *
     * @param string $status The status of the logs to retrieve. Defaults to 'all'
     * @param int $userId The user id for get all logs
     * @param int $page The logs list page number
     *
     * @return array<mixed>|null An array of logs if found, or null if no logs are found
     */
    public function getLogsWhereStatus(string $status = 'all', int $userId = 0, int $page = 1): ?array
    {
        // get page limitter
        $perPage = $this->appUtil->getPageLimiter();

        $repository = $this->entityManager->getRepository(Log::class);

        // calculate offset
        $offset = ($page - 1) * $perPage;

        // get all logs or by status
        if ($status == 'all') {
            if ($userId != 0) {
                $logs = $repository->findBy(['user_id' => $userId], null, $perPage, $offset);
            } else {
                $logs = $repository->findBy([], null, $perPage, $offset);
            }
        } else {
            $logs = $repository->findBy(['status' => $status], ['id' => 'DESC'], $perPage, $offset);
        }

        // log action
        $this->log('log-manager', strtolower($status) . ' logs viewed', level: 3);

        return $logs;
    }

    /**
     * Update the status of a log entry by its ID
     *
     * This method retrieves a log entry by its ID, updates its status to the specified new status
     * and persists the change to the database. If the log entry is not found, it handles the error
     *
     * @param int $id The ID of the log entry to update
     * @param string $newStatus The new status to set for the log entry
     *
     * @return void
     *
     * @throws \Exception If there is an error during the update process
     */
    public function updateLogStatusById(int $id, string $newStatus): void
    {
        $repository = $this->entityManager->getRepository(Log::class);

        /** @var \App\Entity\Log $log */
        $log = $repository->find($id);

        // check if log found
        if (!$log) {
            $this->errorManager->handleError('log status update error: log id: ' . $id . ' not found', 500);
        }

        // update status
        try {
            // update status
            $log->setStatus($newStatus);

            // flush data to database
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to update log status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set all logs with status 'UNREADED' to 'READED'
     *
     * This method fetches logs with status 'UNREADED' using getLogsWhereStatus()
     * updates their status to 'READED', and flushes the changes to the database
     *
     * @throws \Exception If there is an error while updating the log statuses
     *
     * @return void
     */
    public function setAllLogsToReaded(): void
    {
        $repository = $this->entityManager->getRepository(Log::class);

        /** @var \App\Entity\Log $logs */
        $logs = $repository->findBy(['status' => 'UNREADED']);

        if (is_iterable($logs)) {
            // set all logs to readed status
            foreach ($logs as $log) {
                $log->setStatus('READED');
            }
        }

        try {
            // flush changes to the database
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to set all logs status to "READED": ' . $e, 500);
        }
    }

    /**
     * Retrieves a list of system log files
     *
     * @return array<string> An array of relative pathnames of log files found in the /var/log directory
     */
    public function getSystemLogs(): array
    {
        // initialize Finder
        $finder = new Finder();
        $finder->files()->in($this->appUtil->getSystemLogsDirectory());

        // array to store log files
        $logFiles = [];

        // iterate over found files
        foreach ($finder as $file) {
            // check if log is not archived
            if (!str_ends_with($file->getRelativePathname(), '.xz')) {
                $logFiles[] = $file->getRelativePathname();
            }
        }

        return $logFiles;
    }

    /**
     * Retrieves the content of a specific system log file
     *
     * @param string $logFile The relative pathname of the log file to retrieve
     *
     * @return mixed The content of the log file, or null if the file does not exist
     */
    public function getSystemLogContent(string $logFile): mixed
    {
        // check if file exists
        $filePath = $this->appUtil->getSystemLogsDirectory() . '/' . $logFile;
        if (!file_exists($filePath)) {
            $this->errorManager->handleError('error to get log file: ' . $filePath . ' not found', 404);
        }

        // get log file content
        return file_get_contents($filePath);
    }
}
