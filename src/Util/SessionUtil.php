<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;

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
     * Get the current session or null when no HTTP context is present
     *
     * @return SessionInterface|null
     */
    private function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }

    /**
     * Start new session if not already started
     *
     * @return void
     */
    public function startSession(): void
    {
        $session = $this->getSession();
        if ($session !== null && !$session->isStarted()) {
            $session->start();
        }
    }

    /**
     * Destroy current session
     *
     * @return void
     */
    public function destroySession(): void
    {
        $session = $this->getSession();
        if ($session !== null && $session->isStarted()) {
            $session->invalidate();
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
        $session = $this->getSession();
        if ($session === null) {
            return false;
        }

        return $session->has($sessionName);
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
        $session = $this->getSession();
        if ($session === null) {
            return;
        }

        if (!$session->isStarted()) {
            $session->start();
        }

        $session->set($sessionName, $this->securityUtil->encryptAes($sessionValue));
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
        $session = $this->getSession();

        if ($session === null) {
            return $default;
        }

        $value = null;

        try {
            if (!$session->isStarted()) {
                $session->start();
            }

            /** @var string $value */
            $value = $session->get($sessionName);
        } catch (Exception) {
            return $default;
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
        return $this->getSession()?->getId() ?? '';
    }
}
