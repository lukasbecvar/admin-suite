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

        // create the cache util instance
        $this->cacheUtil = new CacheUtil(
            $this->errorManagerMock,
            $this->cacheItemPoolMock
        );
    }

    /**
     * Test check is key catched
     *
     * @return void
     */
    public function testIsCatched(): void
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
     * Test get catched value
     *
     * @return void
     */
    public function testGetValue(): void
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
    public function testSetValue(): void
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
     * Test set cache value with exception
     *
     * @return void
     */
    public function testSetValueWithException(): void
    {
        // testing cache item data
        $key = 'test_key';
        $value = 'test_value';
        $expiration = 3600;

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willThrowException(
            new Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to store cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // call tested method
        $this->cacheUtil->setValue($key, $value, $expiration);
    }

    /**
     * Test delete value from cache
     *
     * @return void
     */
    public function testDeleteValue(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('deleteItem')->with($key);

        // call tested method
        $this->cacheUtil->deleteValue($key);
    }

    /**
     * Test delete value from cache with exception
     *
     * @return void
     */
    public function testDeleteValueException(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('deleteItem')->with($key)->willThrowException(
            new Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to delete cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // call tested method
        $this->cacheUtil->deleteValue($key);
    }
}
