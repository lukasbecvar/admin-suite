<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheUtil
 *
 * Util for manage cache storage
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
     * Check if key exists in the cache storage
     *
     * @param string $key The key to check in cache
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
     * Get value associated with given key from the cache storage
     *
     * @param string $key The key for which to retrieve the value
     *
     * @return CacheItemInterface The cached value associated with the key, or null if not found
     */
    public function getValue(string $key): CacheItemInterface
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
     * Set value in cache with specified key and expiration time
     *
     * @param string $key The key under which to store value in the cache
     * @param mixed $value The value to store in cache
     * @param int $expiration The expiration time in seconds for cached value
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
     * Delete value from cache storage by key
     *
     * @param string $key The key of the value to delete from cache
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
