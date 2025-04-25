<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SessionUtil
 *
 * Util for session management
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
     * Start new session if not already started
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
     * Destroy current session
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
     * Check if session with the specified name exists
     *
     * @param string $sessionName The name of the session to check
     *
     * @return bool Session exists status
     */
    public function checkSession(string $sessionName): bool
    {
        return $this->requestStack->getSession()->has($sessionName);
    }

    /**
     * Set session value
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
     * Get session value
     *
     * @param string $sessionName The name of the session
     *
     * @return mixed The decrypted session value
     */
    public function getSessionValue(string $sessionName, mixed $default = null): mixed
    {
        $value = null;

        try {
            // start session
            $this->startSession();

            /** @var string $value */
            $value = $this->requestStack->getSession()->get($sessionName);
        } catch (Exception) {
            return null;
        }

        // check if session value get
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

        // return decrypted session value
        return $value;
    }

    /**
     * Get session id
     *
     * @return string Session id
     */
    public function getSessionId(): string
    {
        return $this->requestStack->getSession()->getId();
    }
}
