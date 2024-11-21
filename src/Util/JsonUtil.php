<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonUtil
 *
 * Util for get JSON data from a file or URL
 *
 * @package App\Util
 */
class JsonUtil
{
    private ErrorManager $errorManager;

    public function __construct(ErrorManager $errorManager)
    {
        $this->errorManager = $errorManager;
    }

    /**
     * Get JSON data from a file or URL
     *
     * @param string $target The file path or URL
     *
     * @throws Exception If an error occurs while retrieving the JSON data
     *
     * @return array<mixed>|null The decoded JSON data as an associative array or null on failure
     */
    public function getJson($target): ?array
    {
        // request context
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: admin-suite'
                ],
                'timeout' => 5
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
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get json data from ' . $target . ' with error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
