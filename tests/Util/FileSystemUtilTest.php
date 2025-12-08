<?php

namespace App\Tests\Util;

use App\Util\FileSystemUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class FileSystemUtilTest
 *
 * @package App\Tests\Util
 */
#[Group('file-system')]
#[CoversClass(FileSystemUtil::class)]
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
     * Test is path directory
     *
     * @return void
     */
    public function testIsPathDirectory(): void
    {
        // create temporary directory
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);

        // test directory
        $this->assertTrue($this->fileSystemUtil->isPathDirectory($tempDir));

        // test non-directory
        $this->assertFalse($this->fileSystemUtil->isPathDirectory('/etc/os-release'));

        // clean up
        rmdir($tempDir);
    }

    public function testCheckIfFileExist(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // test existing file
        $this->assertTrue($this->fileSystemUtil->checkIfFileExist($textFile));

        // test non-existent file
        $this->assertFalse($this->fileSystemUtil->checkIfFileExist('/non/existent/file'));

        // clean up
        unlink($textFile);
    }

    /**
     * Test get file permissions
     *
     * @return void
     */
    public function testGetFilePermissions(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // test existing file
        $this->assertIsInt($this->fileSystemUtil->getFilePermissions($textFile));

        // test non-existent file
        $this->assertNull($this->fileSystemUtil->getFilePermissions('/non/existent/file'));

        // clean up
        unlink($textFile);
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
        $this->assertIsString($result['content']);
    }

    /**
     * Test save file content
     *
     * @return void
     */
    public function testSaveFileContent(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        $originalContent = 'Original content';
        file_put_contents($textFile, $originalContent);

        // new content to save
        $newContent = 'New content for testing';

        // test saving content to file
        $this->assertTrue($this->fileSystemUtil->saveFileContent($textFile, $newContent));

        // verify content was saved
        $this->assertEquals($newContent . "\n", file_get_contents($textFile));

        // clean up
        unlink($textFile);
    }

    /**
     * Test delete file or directory
     *
     * @return void
     */
    public function testDeleteFileOrDirectory(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // create temporary directory
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);

        // test deleting file
        $this->assertTrue($this->fileSystemUtil->deleteFileOrDirectory($textFile));
        $this->assertFileDoesNotExist($textFile);

        // test deleting directory
        $this->assertTrue($this->fileSystemUtil->deleteFileOrDirectory($tempDir));
        $this->assertDirectoryDoesNotExist($tempDir);
    }

    /**
     * Test rename file or directory
     *
     * @return void
     */
    public function testRenameFileOrDirectory(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // new file name
        $newFile = sys_get_temp_dir() . '/renamed_test_' . uniqid();

        // test renaming file
        $this->assertTrue($this->fileSystemUtil->renameFileOrDirectory($textFile, $newFile));
        $this->assertFileDoesNotExist($textFile);
        $this->assertFileExists($newFile);

        // clean up
        unlink($newFile);
    }

    /**
     * Test calculate directory size
     *
     * @return void
     */
    public function testCalculateDirectorySize(): void
    {
        // create temporary directory
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);

        // create some files in the directory
        file_put_contents($tempDir . '/file1.txt', str_repeat('a', 1000));
        file_put_contents($tempDir . '/file2.txt', str_repeat('b', 2000));

        // test calculating directory size
        $size = $this->fileSystemUtil->calculateDirectorySize($tempDir);
        $this->assertGreaterThanOrEqual(3000, $size);

        // clean up
        unlink($tempDir . '/file1.txt');
        unlink($tempDir . '/file2.txt');
        rmdir($tempDir);
    }

    /**
     * Test format file size
     *
     * @return void
     */
    public function testFormatFileSize(): void
    {
        // test various file sizes
        $this->assertEquals('0 B', $this->fileSystemUtil->formatFileSize(0));
        $this->assertEquals('100 B', $this->fileSystemUtil->formatFileSize(100));
        $this->assertEquals('1 KB', $this->fileSystemUtil->formatFileSize(1024));
        $this->assertEquals('1.5 KB', $this->fileSystemUtil->formatFileSize(1536));
        $this->assertEquals('1 MB', $this->fileSystemUtil->formatFileSize(1048576));
        $this->assertEquals('1 GB', $this->fileSystemUtil->formatFileSize(1073741824));
    }

    /**
     * Test move file or directory
     *
     * @return void
     */
    public function testMoveFileOrDirectory(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($textFile, 'Test content');

        // create destination directory
        $destDir = sys_get_temp_dir() . '/test_dest_' . uniqid();
        mkdir($destDir);

        // test moving file
        $this->assertTrue($this->fileSystemUtil->moveFileOrDirectory($textFile, $destDir));
        $this->assertFileDoesNotExist($textFile);
        $this->assertFileExists($destDir . '/' . basename($textFile));

        // clean up
        unlink($destDir . '/' . basename($textFile));
        rmdir($destDir);
    }

    /**
     * Test get full file content
     *
     * @return void
     */
    public function testGetFullFileContent(): void
    {
        // create temporary text file
        $textFile = tempnam(sys_get_temp_dir(), 'test_');
        $content = "Line 1\nLine 2\nLine 3";
        file_put_contents($textFile, $content);

        // call tested method
        $result = $this->fileSystemUtil->getFullFileContent($textFile);

        // assert result
        $this->assertEquals($content, $result);

        // clean up
        unlink($textFile);
    }
}
