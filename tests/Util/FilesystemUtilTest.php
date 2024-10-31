<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\FileSystemUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;

/**
 * Class FileSystemUtilTest
 *
 * This class tests the FileSystemUtil class
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

        // create the filesystem util instance
        $this->fileSystemUtil = new FileSystemUtil(
            $this->appUtil,
            $this->errorManager
        );
    }

    /**
     * Test the getFilesList method
     *
     * @return void
     */
    public function testGetFilesListSuccess(): void
    {
        // get the list of files
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
     * Test the isFileExecutable method
     *
     * @return void
     */
    public function testIsFileExecutableSuccess(): void
    {
        $result = $this->fileSystemUtil->isFileExecutable('/var/www/balbla.txt');

        // assert result is bool
        $this->assertIsBool($result);
    }

    /**
     * Test the detectMediaType method
     *
     * @return void
     */
    public function testDetectMediaTypeSuccess(): void
    {
        $result = $this->fileSystemUtil->detectMediaType('/var/www/balbla.txt');

        // assert result is string
        $this->assertIsString($result);
    }

    /**
     * Test the getFileContent method
     *
     * @return void
     */
    public function testGetFileContentSuccess(): void
    {
        $result = $this->fileSystemUtil->getFileContent('/usr/lib/os-release');

        // assert result is string
        $this->assertIsString($result);
    }
}
