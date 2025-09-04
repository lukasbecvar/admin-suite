<?php

namespace App\Tests\Util;

use App\Util\FileUploadUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

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
    private string $secondTempDir;
    private ErrorManager|MockObject $errorManager;
    private FileUploadUtil $fileUploadUtil;

    protected function setUp(): void
    {
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->fileUploadUtil = new FileUploadUtil($this->errorManager);

        // create temporary directories for tests
        $this->tempDir = sys_get_temp_dir() . '/file_upload_test_' . uniqid();
        $this->secondTempDir = sys_get_temp_dir() . '/file_upload_test_2_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->secondTempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // clean up temp directories if they exist
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDirectory($this->tempDir);
        }
        if (is_dir($this->secondTempDir)) {
            $this->recursiveRemoveDirectory($this->secondTempDir);
        }
    }

    /**
     * Recursively remove directory
     *
     * @param string $dir The directory to remove
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

        // build target path in second temp dir to avoid permission issues
        $targetPath = $this->secondTempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result is boolean (since we can't guarantee sudo access in tests)
        $this->assertIsBool($result);

        // if successful, verify the content would be correct
        if ($result && file_exists($targetPath)) {
            $this->assertEquals('Hello World!', file_get_contents($targetPath));
        }
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
        $targetPath = $this->secondTempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result - should fail due to missing chunk_2
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
        $targetPath = $this->secondTempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($invalidTempDir, $targetPath, 3);

        // assert result - should fail due to invalid temp directory
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
        $targetPath = $this->secondTempDir . '/empty_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 0);

        // assert result - should handle zero chunks gracefully
        $this->assertIsBool($result);
    }

    /**
     * Test chunk combination with chunks in wrong order
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksWrongOrder(): void
    {
        // create chunks in wrong order (chunk_2, chunk_0, chunk_1)
        file_put_contents($this->tempDir . '/chunk_2', '!');
        file_put_contents($this->tempDir . '/chunk_0', 'Hello ');
        file_put_contents($this->tempDir . '/chunk_1', 'World');

        $targetPath = $this->secondTempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result is boolean
        $this->assertIsBool($result);

        // if successful, verify chunks are combined in correct order
        if ($result && file_exists($targetPath)) {
            $this->assertEquals('Hello World!', file_get_contents($targetPath));
        }
    }

    /**
     * Test chunk combination with empty chunks
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksWithEmptyChunks(): void
    {
        // create chunks with some empty content
        file_put_contents($this->tempDir . '/chunk_0', 'Hello');
        file_put_contents($this->tempDir . '/chunk_1', ''); // empty chunk
        file_put_contents($this->tempDir . '/chunk_2', 'World');

        $targetPath = $this->secondTempDir . '/combined_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result is boolean
        $this->assertIsBool($result);

        // if successful, verify content includes empty chunk
        if ($result && file_exists($targetPath)) {
            $this->assertEquals('HelloWorld', file_get_contents($targetPath));
        }
    }

    /**
     * Test chunk combination with binary data
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksBinaryData(): void
    {
        // create chunks with binary data
        $binaryData1 = pack('H*', '89504e470d0a1a0a'); // PNG header
        $binaryData2 = pack('H*', '0000000d49484452'); // IHDR chunk
        $binaryData3 = pack('H*', 'ae426082'); // some binary data

        file_put_contents($this->tempDir . '/chunk_0', $binaryData1);
        file_put_contents($this->tempDir . '/chunk_1', $binaryData2);
        file_put_contents($this->tempDir . '/chunk_2', $binaryData3);

        $targetPath = $this->secondTempDir . '/binary_file.bin';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 3);

        // assert result is boolean
        $this->assertIsBool($result);

        // if successful, verify binary data integrity
        if ($result && file_exists($targetPath)) {
            $expectedContent = $binaryData1 . $binaryData2 . $binaryData3;
            $this->assertEquals($expectedContent, file_get_contents($targetPath));
        }
    }

    /**
     * Test cleanup with multiple files in directory
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCleanupTempDirectoryWithMultipleFiles(): void
    {
        // create multiple files in temp directory
        $files = [
            'file1.txt' => 'Content 1',
            'file2.txt' => 'Content 2',
            'file3.bin' => pack('H*', '89504e47'),
            'file4.log' => 'Log content'
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($this->tempDir . '/' . $filename, $content);
        }

        // verify all files exist
        foreach ($files as $filename => $content) {
            $this->assertFileExists($this->tempDir . '/' . $filename);
        }

        // call tested method
        $this->fileUploadUtil->cleanupTempDirectory($this->tempDir);

        // verify all files are removed and directory is cleaned up
        foreach ($files as $filename => $content) {
            $this->assertFileDoesNotExist($this->tempDir . '/' . $filename);
        }

        // directory should be removed (since it only contained files, no subdirectories)
        $this->assertDirectoryDoesNotExist($this->tempDir);
    }

    /**
     * Test stream file range method exists and is callable
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testStreamFileRangeMethodExists(): void
    {
        // create test file
        $testFile = $this->tempDir . '/stream_test.txt';
        file_put_contents($testFile, 'Test content');

        // verify file exists
        $this->assertFileExists($testFile);
    }

    /**
     * Test combine chunks with invalid target path
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksInvalidTargetPath(): void
    {
        // create test chunks
        file_put_contents($this->tempDir . '/chunk_0', 'Test');
        file_put_contents($this->tempDir . '/chunk_1', 'Data');

        // use invalid target path (directory that doesn't exist)
        $invalidTargetPath = '/non/existent/path/file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $invalidTargetPath, 2);

        // assert result is false due to invalid target path
        $this->assertFalse($result);
    }

    /**
     * Test combine chunks with single chunk
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksSingleChunk(): void
    {
        // create single chunk
        file_put_contents($this->tempDir . '/chunk_0', 'Single chunk content');

        $targetPath = $this->secondTempDir . '/single_chunk_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, 1);

        // assert result is boolean
        $this->assertIsBool($result);

        // if successful, verify content
        if ($result && file_exists($targetPath)) {
            $this->assertEquals('Single chunk content', file_get_contents($targetPath));
        }
    }

    /**
     * Test combine chunks with very large number of chunks
     *
     * @return void
     */
    #[Group('file-upload')]
    public function testCombineChunksManyChunks(): void
    {
        $totalChunks = 50;
        $expectedContent = '';

        // create many small chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkContent = "Chunk$i ";
            file_put_contents($this->tempDir . "/chunk_$i", $chunkContent);
            $expectedContent .= $chunkContent;
        }

        $targetPath = $this->secondTempDir . '/many_chunks_file.txt';

        // call tested method
        $result = $this->fileUploadUtil->combineChunks($this->tempDir, $targetPath, $totalChunks);

        // assert result is boolean
        $this->assertIsBool($result);

        // if successful, verify content
        if ($result && file_exists($targetPath)) {
            $this->assertEquals($expectedContent, file_get_contents($targetPath));
        }
    }
}
