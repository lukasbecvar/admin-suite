<?php

namespace App\Util;

/**
 * Class SecurityUtil
 *
 * The utility class for security
 *
 * @package App\Util
 */
class SecurityUtil
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Escape the string
     *
     * @param string $string The string to escape
     *
     * @return string|null The escaped string
     */
    public function escapeString(string $string): ?string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * Generate hash for a given password.
     *
     * @param string $password The password to hash.
     *
     * @return string The hashed password.
     */
    public function generateHash(string $password): string
    {
        $config = $this->appUtil->getHasherConfig();

        $options = [
            'memory_cost' => $config['memory_cost'],
            'time_cost' => $config['time_cost'],
            'threads' => $config['threads']
        ];

        // generate hash
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Verify a password against a given Argon2 hash.
     *
     * @param string $password The password to verify.
     * @param string $hash The hash to verify against.
     *
     * @return bool True if the password is valid, false otherwise.
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
