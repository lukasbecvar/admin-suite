<?php

namespace App\Util;

use Exception;

/**
 * Class JsonUtil
 *
 * Util for get JSON data from file or URL
 *
 * @package App\Util
 */
class JsonUtil
{
    /**
     * Get JSON data from file or URL
     *
     * @param string $target The file path or URL
     * @param int $timeout The timeout in seconds (default: 5)
     *
     * @return array<mixed>|null The decoded JSON data in associative array
     */
    public function getJson(string $target, int $timeout = 5): ?array
    {
        // request context
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: admin-suite'
                ],
                'timeout' => $timeout
            ]
        ]);

        try {
            // get data
            $data = file_get_contents($target, false, $context);

            // return null if data retrieval fails
            if ($data == null) {
                return null;
            }

            // decode & return json
            return (array) json_decode($data, true);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Check if string is valid JSON format
     *
     * @param string $string The string to check
     *
     * @return bool True if string is valid JSON format, false otherwise
     */
    public function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
