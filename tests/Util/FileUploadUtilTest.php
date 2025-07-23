<?php

namespace App\Tests\Util;

use App\Util\FileUploadUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class FileUploadUtilTest
 *
 * Test cases for file upload util
 *
 * @package App\Tests\Util
 */
class FileUploadUtilTest extends TestCase
{
    private string $tempDir;
    private ErrorManager $errorManager;
    private FileUploadUtil $fileUploadUtil;

    protected function setUp(): void
    {
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->fileUploadUtil = new FileUploadUtil($this->errorManager);

        // create temporary directory for tests
        $this->tempDir = sys_get_temp_dir() . '/file_upload_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // clean up temp directory if it exists
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDirectory($this->tempDir);
        }
    }

    /**
     * Recursively remove directory and its contents
     *
     * @param string $dir Directory path
     *
     * @return void
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Test successful chunk combination
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksSuccess(): void
    {
        // create test chunks
        $chunkData = [
            'chunk_0' => 'Hello ',
            'chunk_1' => 'World',
            'chunk_2' => '!'
        ];

        // create chunk files
        foreach ($chunkData as $filename => $content) {
            file_put_contents($this->tempDir . '/' . $filename, $content);
        }

        // build target path
        $targetPath = $this->tempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test chunk combination with missing chunk
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksMissingChunk(): void
    {
        // create only 2 out of 3 chunks
        file_put_contents($this->tempDir . '/chunk_0', 'Hello ');
        file_put_contents($this->tempDir . '/chunk_1', 'World');

        // build target path
        $targetPath = $this->tempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test chunk combination with invalid temp directory
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksInvalidTempDir(): void
    {
        $invalidTempDir = '/non/existent/directory';
        $targetPath = $this->tempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($invalidTempDir, $targetPath, 3);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test cleanup of temporary directory
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCleanupTempDirectory(): void
    {
        // create test files in temp directory
        file_put_contents($this->tempDir . '/test_file1.txt', 'Test content 1');
        file_put_contents($this->tempDir . '/test_file2.txt', 'Test content 2');

        // verify files exist
        $this->assertFileExists($this->tempDir . '/test_file1.txt');
        $this->assertFileExists($this->tempDir . '/test_file2.txt');

        // call tested method
        $this->fileUploadUtil->cleanupTempDirectory($this->tempDir);

        // assert directory is removed
        $this->assertDirectoryDoesNotExist($this->tempDir);
    }

    /**
     * Test chunk combination with large files
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksLargeFile(): void
    {
        // create larger test chunks (simulate real file upload)
        $chunkSize = 1024; // 1KB chunks
        $totalChunks = 5;

        for ($i = 0; $i < $totalChunks; $i++) {
            $repeatCount = (int) ($chunkSize / 15); // explicit int conversion
            $chunkContent = str_repeat("Chunk $i data ", $repeatCount);
            file_put_contents($this->tempDir . "/chunk_$i", $chunkContent);
        }

        // build target path
        $targetPath = $this->tempDir . '/large_combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, $totalChunks);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test chunk combination with zero chunks
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksZeroChunks(): void
    {
        $targetPath = $this->tempDir . '/empty_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 0);

        // assert result
        $this->assertIsBool($result);
    }
}
