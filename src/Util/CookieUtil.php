<?php

namespace App\Util;

use Exception;
use App\Util\SecurityUtil;

/**
 * Class CookieUtil
 *
 * Util for manage browser cookies
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
     * Set cookie with specified name, value, and expiration
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
            // encrypt cookie value
            $value = $this->securityUtil->encryptAes($value);
            $value = base64_encode($value);

            // set cookie
            setcookie($name, $value, $expiration, '/', httponly: true);
        }
    }

    /**
     * Check if specified cookie is set
     *
     * @param string $name The name of the cookie
     */
    public function isCookieSet(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Get value of the specified cookie
     *
     * @param string $name The name of the cookie
     *
     * @return string|null The decrypted value of the cookie
     */
    public function get(string $name): ?string
    {
        // get value from cookie
        $value = base64_decode($_COOKIE[$name]);

        // decrypt value
        $value = $this->securityUtil->decryptAes($value);
        return $value;
    }

    /**
     * Unset (delete) the specified cookie
     *
     * @param string $name The name of the cookie
     *
     * @throws Exception If the URI is invalid
     *
     * @return void
     */
    public function unset(string $name): void
    {
        if (!headers_sent()) {
            $host = $_SERVER['HTTP_HOST'];
            $domain = explode(':', $host)[0];
            $uri = $_SERVER['REQUEST_URI'];
            $uri = rtrim(explode('?', $uri)[0], '/');

            if ($uri && !filter_var('file://' . $uri, FILTER_VALIDATE_URL)) {
                throw new Exception('invalid uri: ' . $uri);
            }

            $parts = explode('/', $uri);
            $cookiePath = '';

            // unset the cookie for each part of the URI.
            foreach ($parts as $part) {
                $cookiePath = '/' . ltrim($cookiePath . '/' . $part, '//');
                setcookie($name, '', 1, $cookiePath, httponly: true);
                do {
                    setcookie($name, '', 1, $cookiePath, $domain, httponly: true);
                } while (strpos($domain, '.') !== false && $domain = substr($domain, 1 + strpos($domain, '.')));
            }
        }
    }
}
