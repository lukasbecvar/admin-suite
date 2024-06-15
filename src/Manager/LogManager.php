<?php

namespace App\Manager;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
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
    public function log(string $name, string $message, int $level = 3): void
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
            $this->errorManager->handleError('log-error: ' . $e->getMessage(), 500);
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
}
