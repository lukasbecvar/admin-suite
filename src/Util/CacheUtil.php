<?php

namespace App\Util;

use App\Manager\ErrorManager;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheUtil
 *
 * Manages caching operations using a cache item pool
 *
 * @package App\Util
 */
class CacheUtil
{
    private ErrorManager $errorManager;
    private CacheItemPoolInterface $cacheItemPoolInterface;

    public function __construct(
        ErrorManager $errorManager,
        CacheItemPoolInterface $cacheItemPoolInterface
    ) {
        $this->errorManager = $errorManager;
        $this->cacheItemPoolInterface = $cacheItemPoolInterface;
    }

    /**
     * Checks if a key exists in the cache
     *
     * @param string $key The key to check in the cache
     *
     * @throws Exception If an error occurs while checking the cache
     *
     * @return bool True if the key exists in the cache, otherwise false
     */
    public function isCatched(string $key): bool
    {
        try {
            return $this->cacheItemPoolInterface->getItem($key)->isHit();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get the value associated with a given key from the cache
     *
     * @param string $key The key for which to retrieve the value
     *
     * @throws Exception If an error occurs while retrieving the cache value
     *
     * @return object|null The cached value associated with the key, or null if not found
     */
    public function getValue(string $key): ?object
    {
        try {
            return $this->cacheItemPoolInterface->getItem($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Sets a value in the cache with the specified key and expiration time
     *
     * @param string $key The key under which to store the value in the cache
     * @param mixed $value The value to store in the cache.
     * @param int $expiration The expiration time in seconds for the cached value
     *
     * @throws Exception If an error occurs while storing the cache value
     *
     * @return void
     */
    public function setValue(string $key, mixed $value, int $expiration): void
    {
        try {
            // set cache value data
            $cache_item = $this->cacheItemPoolInterface->getItem($key);
            $cache_item->set($value);
            $cache_item->expiresAfter($expiration);

            // save value
            $this->cacheItemPoolInterface->save($cache_item);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to store cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete value from the cache using the specified key
     *
     * @param string $key The key of the value to delete from the cache
     *
     * @throws Exception If an error occurs while deleting the cache value
     *
     * @return void
     */
    public function deleteValue(string $key): void
    {
        try {
            $this->cacheItemPoolInterface->deleteItem($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
