<?php

namespace App\Tests\Util;

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
 * Test the cache util
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

        // assert that the method returns true
        $this->assertTrue($this->cacheUtil->isCatched($key));
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

        // assert that the method returns the cache item
        $this->assertSame($cacheItemMock, $this->cacheUtil->getValue($key));
    }

    /**
     * Test set value in cache
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

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willReturn($cacheItemMock);
        $this->cacheItemPoolMock->expects($this->once())->method('save')->with($cacheItemMock);

        // set cache item mock expectations
        $cacheItemMock->expects($this->once())->method('set')->with($value);
        $cacheItemMock->expects($this->once())->method('expiresAfter')->with($expiration);

        // call the method
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

        // call the method
        $this->cacheUtil->deleteValue($key);
    }

    /**
     * Test the isCatched exception
     *
     * @return void
     */
    public function testIsCatchedException(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willThrowException(
            new \Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to get cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // assert that the method returns false
        $this->assertFalse($this->cacheUtil->isCatched($key));
    }

    /**
     * Test the getValue exception
     *
     * @return void
     */
    public function testGetValueException(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willThrowException(
            new \Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to get cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // assert that the method returns null
        $this->assertNull($this->cacheUtil->getValue($key));
    }

    /**
     * Test the setValue exception
     *
     * @return void
     */
    public function testSetValueException(): void
    {
        // testing cache item data
        $key = 'test_key';
        $value = 'test_value';
        $expiration = 3600;

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('getItem')->with($key)->willThrowException(
            new \Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to store cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // call the method
        $this->cacheUtil->setValue($key, $value, $expiration);
    }

    /**
     * Test the deleteValue exception
     *
     * @return void
     */
    public function testDeleteValueException(): void
    {
        // testing cache item key
        $key = 'test_key';

        // set cache item mock expectations
        $this->cacheItemPoolMock->expects($this->once())->method('deleteItem')->with($key)->willThrowException(
            new \Exception('Test exception')
        );

        // set error manager mock expectations
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with('error to delete cache value: Test exception', Response::HTTP_INTERNAL_SERVER_ERROR);

        // call the method
        $this->cacheUtil->deleteValue($key);
    }
}
