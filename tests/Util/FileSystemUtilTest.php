<?php

namespace App\Tests\Util;

use App\Util\FileSystemUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class FileSystemUtilTest
 *
 * @package App\Tests\Util
 */
class FileSystemUtilTest extends TestCase
{
    private FileSystemUtil $fileSystemUtil;
    private ErrorManager $errorManager;

    /**
     * Set up test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->fileSystemUtil = new FileSystemUtil($this->errorManager);
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
     * Test is file editable
     *
     * @return void
     */
    #[Group('file-system')]
    public function testIsFileEditable(): void
    {
        // Create a temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // Create a temporary directory
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir);

        // Test text file is editable
        $this->assertTrue($this->fileSystemUtil->isFileEditable($textFile));

        // Test directory is not editable
        $this->assertFalse($this->fileSystemUtil->isFileEditable($tempDir));

        // Test non-existent file is not editable
        $this->assertFalse($this->fileSystemUtil->isFileEditable('/non/existent/file'));

        // Clean up
        unlink($textFile);
        rmdir($tempDir);
    }

    /**
     * Test create directory
     *
     * @return void
     */
    #[Group('file-system')]
    public function testCreateDirectory(): void
    {
        // create a temporary directory path
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();

        // test create directory
        $this->assertTrue($this->fileSystemUtil->createDirectory($tempDir));
        $this->assertDirectoryExists($tempDir);

        // test create nested directory
        $nestedDir = $tempDir . '/nested/dir';
        $this->assertTrue($this->fileSystemUtil->createDirectory($nestedDir));
        $this->assertDirectoryExists($nestedDir);

        // clean up
        rmdir($nestedDir);
        rmdir($tempDir . '/nested');
        rmdir($tempDir);
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
