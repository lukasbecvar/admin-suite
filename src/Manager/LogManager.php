<?php

namespace App\Manager;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\EntityManagerInterface;

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
    private ErrorManager $errorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, VisitorInfoUtil $visitorInfoUtil, EntityManagerInterface $entityManager)
    {
        $this->appUtil = $appUtil;
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
    public function log(string $name, string $message, int $level = 3): void
    {
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

        try {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('log-error: ' . $e->getMessage(), 500);
        }
    }
}
