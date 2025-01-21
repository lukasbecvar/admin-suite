<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\FileSystemUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;

/**
 * Class FileSystemUtilTest
 *
 * Test cases for file system util
 *
 * @package App\Tests\Util
 */
class FileSystemUtilTest extends TestCase
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;
    private FileSystemUtil $fileSystemUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);

        // create filesystem util instance
        $this->fileSystemUtil = new FileSystemUtil(
            $this->appUtil,
            $this->errorManager
        );
    }

    /**
     * Test get file list
     *
     * @return void
     */
    public function testGetFilesList(): void
    {
        // call tested method
        $list = $this->fileSystemUtil->getFilesList('/');

        // check result array
        $this->assertIsArray($list);
        $this->assertArrayHasKey('name', $list[0]);
        $this->assertArrayHasKey('size', $list[0]);
        $this->assertArrayHasKey('permissions', $list[0]);
        $this->assertArrayHasKey('isDir', $list[0]);
        $this->assertArrayHasKey('path', $list[0]);
    }

    /**
     * Test check if file is executable when file is not executable
     *
     * @return void
     */
    public function testCheckIfFileIsExecutableWhenFileIsNotExecutable(): void
    {
        // call tested method
        $result = $this->fileSystemUtil->isFileExecutable('/etc/os-release');

        // assert result is bool
        $this->assertFalse($result);
    }

    /**
     * Test check if file is executable when file is executable
     *
     * @return void
     */
    public function testCheckIfFileIsExecutableWhenFileIsExecutable(): void
    {
        // call tested method
        $result = $this->fileSystemUtil->isFileExecutable('/usr/bin/ls');

        // assert result is bool
        $this->assertTrue($result);
    }

    /**
     * Test detect media type
     *
     * @return void
     */
    public function testDetectMediaType(): void
    {
        // call tested method
        $result = $this->fileSystemUtil->detectMediaType('/etc/os-release');

        // assert result
        $this->assertIsString($result);
        $this->assertEquals('non-mediafile', $result);
    }

    /**
     * Test get file content
     *
     * @return void
     */
    public function testGetFileContent(): void
    {
        // call tested method
        $result = $this->fileSystemUtil->getFileContent('/usr/lib/os-release');

        // assert result is string
        $this->assertIsString($result);
    }
}
