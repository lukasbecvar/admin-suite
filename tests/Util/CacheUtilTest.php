<?php

namespace App\Tests\Util;

use Exception;
use App\Util\CacheUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheUtilTest
 *
 * Test cases for cache util
 *
 * @package App\Tests\Util
 */
class CacheUtilTest extends TestCase
{
    private CacheUtil $cacheUtil;
    private ErrorManager & MockObject $errorManagerMock;
    private CacheItemPoolInterface & MockObject $cacheItemPoolMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->cacheItemPoolMock = $this->createMock(CacheItemPoolInterface::class);

        // create cache util instance
        $this->cacheUtil = new CacheUtil(
            $this->errorManagerMock,
            $this->cacheItemPoolMock
        );
    }

    /**
     * Test check if key exists in cache when key exists
     *
     * @return void
     */
    public function testIsCatchedWhenKeyExists(): void
    {
        // mock cache item
        $key = 'test_key';
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willReturn($cacheItemMock);
        $cacheItemMock->expects($this->once())->method('isHit')->willReturn(true);

        // call tested method
        $result = $this->cacheUtil->isCatched($key);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if key exists in cache when key does not exist
     *
     * @return void
     */
    public function testIsCatchedWhenKeyDoesNotExist(): void
    {
        // mock cache item
        $key = 'test_key';
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willReturn($cacheItemMock);
        $cacheItemMock->expects($this->once())->method('isHit')->willReturn(false);

        // call tested method
        $result = $this->cacheUtil->isCatched($key);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get value from cache storage
     *
     * @return void
     */
    public function testGetValueFromCacheStorage(): void
    {
        // testing item key
        $key = 'test_key';

        // set cache item mock expectations
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willReturn($cacheItemMock);

        // call tested method
        $result = $this->cacheUtil->getValue($key);

        // assert result
        $this->assertSame($cacheItemMock, $result);
    }

    /**
     * Test save value to cache storage
     *
     * @return void
     */
    public function testSetValueToCacheStorage(): void
    {
        // testing cache item
        $key = 'test_key';
        $value = 'test_value';
        $expiration = 3600;
        $cacheItemMock = $this->createMock(CacheItemInterface::class);

        // mock cache item
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willReturn($cacheItemMock);
        $this->cacheItemPoolMock->expects($this->once())->method('save')->with($cacheItemMock);

        // expect method calls
        $cacheItemMock->expects($this->once())->method('set')->with($value);
        $cacheItemMock->expects($this->once())->method('expiresAfter')->with($expiration);

        // call tested method
        $this->cacheUtil->setValue($key, $value, $expiration);
    }

    /**
     * Test set cache value when exception thrown
     *
     * @return void
     */
    public function testSetValueWhenExceptionThrown(): void
    {
        // testing cache item data
        $key = 'test_key';
        $value = 'test_value';
        $expiration = 3600;

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willThrowException(
            new Exception('Test exception')
        );

        // expect call error handler
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to store cache value: Test exception',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->cacheUtil->setValue($key, $value, $expiration);
    }

    /**
     * Test delete value from cache storage
     *
     * @return void
     */
    public function testDeleteValueFromCacheStorage(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('deleteItem')->with($key);

        // call tested method
        $this->cacheUtil->deleteValue($key);
    }

    /**
     * Test delete value from cache storage when exception thrown
     *
     * @return void
     */
    public function testDeleteValueFromCacheStorageWhenExceptionThrown(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('deleteItem')->with($key)->willThrowException(
            new Exception('Test exception')
        );

        // expect call error handler
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to delete cache value: Test exception',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->cacheUtil->deleteValue($key);
    }
}
