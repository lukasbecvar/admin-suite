<?php

namespace App\Util;

/**
 * Class SecurityUtil
 *
 * Util for security related functionality
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
     * Escape string
     *
     * @param string $string The string to escape
     *
     * @return string|null The escaped string
     */
    public function escapeString(string $string): ?string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate hash for given password
     *
     * @param string $password The password to hash
     *
     * @return string The hashed password
     */
    public function generateHash(string $password): string
    {
        $config = $this->appUtil->getHasherConfig();

        $options = [
            'threads' => $config['threads'],
            'time_cost' => $config['time_cost'],
            'memory_cost' => $config['memory_cost']
        ];

        // generate hash
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Verify if password hash is valid password
     *
     * @param string $password The password to verify
     * @param string $hash The hash to verify
     *
     * @return bool True if password is valid, false otherwise
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Encrypt string using AES encryption
     *
     * @param string $plainText The plain text to encrypt
     * @param string $method The encryption method (default: AES-128-CBC)
     *
     * @return string The base64-encoded encrypted string
     */
    public function encryptAes(string $plainText, string $method = 'AES-128-CBC'): string
    {
        $key = $_ENV['APP_SECRET'];

        // derive fixed-size key using PBKDF2 with SHA-256
        $derivedKey = hash_pbkdf2("sha256", $key, "", 10000, 32);

        // generate random Initialization Vector (IV) for added security
        $iv = openssl_random_pseudo_bytes(16);

        // encrypt plain text using AES encryption with the derived key and IV
        $encryptedData = openssl_encrypt($plainText, $method, $derivedKey, 0, $iv);

        // IV and encrypted data, then base64 encode the result
        $result = $iv . $encryptedData;

        return base64_encode($result);
    }

    /**
     * Decrypt AES-encrypted string
     *
     * @param string $encryptedData The base64-encoded encrypted string
     * @param string $method The encryption method (default: AES-128-CBC)
     *
     * @return string|null The decrypted string or null on error
     */
    public function decryptAes(string $encryptedData, string $method = 'AES-128-CBC'): ?string
    {
        $key = $_ENV['APP_SECRET'];

        // derive fixed-size key using PBKDF2 with SHA-256
        $derivedKey = hash_pbkdf2("sha256", $key, "", 10000, 32);

        // decode base64-encoded encrypted data
        $decodedData = base64_decode($encryptedData);

        // extract Initialization Vector (IV) from the decoded data
        $iv = substr($decodedData, 0, 16);

        // extract encrypted data (remaining bytes) from the decoded data
        $encryptedData = substr($decodedData, 16);

        // decrypt data using AES decryption with the derived key and IV
        $decryptedData = openssl_decrypt($encryptedData, $method, $derivedKey, 0, $iv);

        // check if decryption was successful
        if ($decryptedData === false) {
            $decryptedData = null;
        }

        return $decryptedData;
    }
}
