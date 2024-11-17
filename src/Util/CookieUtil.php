<?php

namespace App\Util;

use App\Util\SecurityUtil;

/**
 * Class CookieUtil
 *
 * CookieUtil provides cookie management functionalities
 *
 * @package App\Util
 */
class CookieUtil
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Set a cookie with the specified name, value, and expiration
     *
     * @param string $name The name of the cookie
     * @param string $value The value to store in the cookie
     * @param int $expiration The expiration time for the cookie
     *
     * @return void
     */
    public function set(string $name, string $value, int $expiration): void
    {
        if (!headers_sent()) {
            $value = $this->securityUtil->encryptAes($value);
            $value = base64_encode($value);
            setcookie($name, $value, $expiration, '/');
        }
    }

    /**
     * Check if the specified cookie is set
     *
     * @param string $name The name of the cookie
     */
    public function isCookieSet(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Get the value of the specified cookie
     *
     * @param string $name The name of the cookie
     *
     * @return string|null The decrypted value of the cookie
     */
    public function get(string $name): ?string
    {
        $value = base64_decode($_COOKIE[$name]);
        return $this->securityUtil->decryptAes($value);
    }

    /**
     * Unset (delete) cookie by name
     *
     * @param string $name The name of the cookie
     * @param string $path The path of the cookie (default: '/')
     * @param string $domain The domain of the cookie (default: current host)
     *
     * @return void
     */
    public function unset(string $name, string $path = '/', string $domain = null): void
    {
        if (!headers_sent()) {
            // use the current host as the default domain if not provided
            $domain = $domain ?? $_SERVER['HTTP_HOST'];

            // set the cookie with an expiration time in the past
            setcookie($name, '', time() - 3600, $path, $domain);
        }
    }
}
