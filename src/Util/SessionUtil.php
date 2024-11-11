<?php

namespace App\Util;

use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SessionUtil
 *
 * SessionUtil provides session management functions
 *
 * @package App\Util
 */
class SessionUtil
{
    private RequestStack $requestStack;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;

    public function __construct(RequestStack $requestStack, SecurityUtil $securityUtil, ErrorManager $errorManager)
    {
        $this->requestStack = $requestStack;
        $this->securityUtil = $securityUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Start a new session if not already started
     *
     * @return void
     */
    public function startSession(): void
    {
        if (!$this->requestStack->getSession()->isStarted()) {
            $this->requestStack->getSession()->start();
        }
    }

    /**
     * Destroy the current session
     *
     * @return void
     */
    public function destroySession(): void
    {
        if ($this->requestStack->getSession()->isStarted()) {
            $this->requestStack->getSession()->invalidate();
        }
    }

    /**
     * Check if a session with the specified name exists
     *
     * @param string $sessionName The name of the session to check
     *
     * @return bool Whether the session exists
     */
    public function checkSession(string $sessionName): bool
    {
        return $this->requestStack->getSession()->has($sessionName);
    }

    /**
     * Set a session value.
     *
     * @param string $sessionName The name of the session
     * @param string $sessionValue The value to set for the session
     *
     * @return void
     */
    public function setSession(string $sessionName, string $sessionValue): void
    {
        $this->startSession();
        $this->requestStack->getSession()->set($sessionName, $this->securityUtil->encryptAes($sessionValue));
    }

    /**
     * Get the decrypted value of a session
     *
     * @param string $sessionName The name of the session
     *
     * @return mixed The decrypted session value
     */
    public function getSessionValue(string $sessionName, mixed $default = null): mixed
    {
        $this->startSession();

        /** @var string $value */
        $value = $this->requestStack->getSession()->get($sessionName);

        // check if session exist
        if (!isset($value)) {
            return $default;
        }

        // decrypt session value
        $value = $this->securityUtil->decryptAes($value);

        // check if session data is decrypted
        if ($value === null) {
            $this->destroySession();
            $this->errorManager->handleError(
                message: 'error to decrypt session data',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $value;
    }
}
