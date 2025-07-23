<?php

namespace App\Tests\Controller\Component;

use App\Tests\CustomTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class FileSystemBrowserControllerTest
 *
 * Test cases for file system browser component
 *
 * @package App\Tests\Controller\Component
 */
class FileSystemBrowserControllerTest extends CustomTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // simulate user authentication
        $this->simulateLogin($this->client);
    }

    /**
     * Test load file system list page
     *
     * @return void
     */
    public function testLoadFileSystemBrowserPage(): void
    {
        $this->client->request('GET', '/filesystem');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('body', 'Filesystem');
        $this->assertSelectorExists('a[href="/dashboard"]');
        $this->assertSelectorExists('a[title="Back to dashboard"]');
        $this->assertSelectorExists('a[href="/filesystem?path=/"]');
        $this->assertSelectorTextContains('body', 'Name');
        $this->assertSelectorTextContains('body', 'Size');
        $this->assertSelectorTextContains('body', 'Permissions');
        $this->assertSelectorExists('a[href="/filesystem?path=/root"]');
        $this->assertSelectorTextContains('body', 'root');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load file system view page
     *
     * @return void
     */
    public function testLoadFileSystemViewPage(): void
    {
        $this->client->request('GET', '/filesystem/view?path=/usr/lib/os-release');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/filesystem?path=/usr/lib"]');
        $this->assertSelectorExists('a[title="Back to previous page"]');
        $this->assertSelectorExists('a[href="/filesystem/edit?path=/usr/lib/os-release"]');
        $this->assertSelectorExists('a[title="Edit this file"]');
        $this->assertSelectorTextContains('body', 'os-release');
        $this->assertSelectorExists('pre');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load file system edit page
     *
     * @return void
     */
    public function testLoadFileSystemEditPage(): void
    {
        $this->client->request('GET', '/filesystem/edit?path=/usr/lib/os-release');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/filesystem/view?path=/usr/lib/os-release"]');
        $this->assertSelectorExists('a[title="Back to file view"]');
        $this->assertSelectorTextContains('body', 'File Editor');
        $this->assertSelectorExists('form[action="/filesystem/save"]');
        $this->assertSelectorExists('textarea[id="editor"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('button[type="submit"]', 'Save');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test save file content
     *
     * @return void
     */
    #[Group('file-system')]
    public function testSaveFileContent(): void
    {
        // create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Original content');

        // submit the form with new content
        $this->client->request('POST', '/filesystem/save', [
            'path' => $tempFile,
            'content' => 'New content'
        ]);

        // assert redirect back to file view
        $this->assertResponseRedirects('/filesystem/view?path=' . $tempFile);

        // clean up
        unlink($tempFile);
    }

    /**
     * Test load file system create page
     *
     * @return void
     */
    public function testLoadFileSystemCreatePage(): void
    {
        $this->client->request('GET', '/filesystem/create?path=/tmp');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/filesystem?path=/tmp"]');
        $this->assertSelectorExists('a[title="Back to directory"]');
        $this->assertSelectorTextContains('body', 'Create File');
        $this->assertSelectorExists('form[action="/filesystem/create/save"]');
        $this->assertSelectorExists('input[id="filename"]');
        $this->assertSelectorExists('label[for="filename"]');
        $this->assertSelectorTextContains('label[for="filename"]', 'Filename:');
        $this->assertSelectorExists('textarea[id="editor"]');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('button[type="submit"]', 'Create');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test load file system create directory page
     *
     * @return void
     */
    public function testLoadFileSystemCreateDirectoryPage(): void
    {
        $this->client->request('GET', '/filesystem/create/directory?path=/tmp');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorExists('a[href="/filesystem?path=/tmp"]');
        $this->assertSelectorExists('a[title="Back to directory"]');
        $this->assertSelectorTextContains('body', 'Create Directory');
        $this->assertSelectorExists('form[action="/filesystem/create/directory/save"]');
        $this->assertSelectorExists('input[id="directoryname"]');
        $this->assertSelectorExists('label[for="directoryname"]');
        $this->assertSelectorTextContains('label[for="directoryname"]', 'Directory Name');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('button[type="submit"]', 'Create Directory');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    /**
     * Test create new file
     *
     * @return void
     */
    #[Group('file-system')]
    public function testCreateNewFile(): void
    {
        // create temporary directory for testing
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir);

        // new file path
        $newFilePath = $tempDir . '/test_file.txt';

        // submit form to create new file
        $this->client->request('POST', '/filesystem/create/save', [
            'directory' => $tempDir,
            'filename' => 'test_file.txt',
            'content' => 'Test content'
        ]);

        // assert redirect to file view
        $this->assertResponseRedirects('/filesystem/view?path=' . $newFilePath);

        // clean up
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
        }
        rmdir($tempDir);
    }

    /**
     * Test create new directory
     *
     * @return void
     */
    #[Group('file-system')]
    public function testCreateNewDirectory(): void
    {
        // create temporary directory for testing
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir);

        // new directory path
        $newDirName = 'test_dir_' . uniqid();
        $newDirPath = $tempDir . '/' . $newDirName;

        // submit form to create new directory
        $this->client->request('POST', '/filesystem/create/directory/save', [
            'directory' => $tempDir,
            'directoryname' => $newDirName
        ]);

        // assert redirect to directory
        $this->assertResponseRedirects('/filesystem?path=' . $newDirPath);

        // assert directory was created
        $this->assertDirectoryExists($newDirPath);

        // clean up
        rmdir($newDirPath);
        rmdir($tempDir);
    }

    /**
     * Test delete file
     *
     * @return void
     */
    #[Group('file-system')]
    public function testDeleteFile(): void
    {
        // create temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Test content');

        // get directory path
        $directoryPath = dirname($tempFile);

        // submit form to delete file
        $this->client->request('POST', '/filesystem/delete', [
            'path' => $tempFile
        ]);

        // assert redirect to directory
        $this->assertResponseRedirects('/filesystem?path=' . $directoryPath);

        // assert file was deleted
        $this->assertFileDoesNotExist($tempFile);
    }

    /**
     * Test delete directory
     *
     * @return void
     */
    #[Group('file-system')]
    public function testDeleteDirectory(): void
    {
        // create temporary directory for testing
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir);

        // get parent directory path
        $parentDir = dirname($tempDir);

        // submit form to delete directory
        $this->client->request('POST', '/filesystem/delete', [
            'path' => $tempDir
        ]);

        // assert redirect to parent directory
        $this->assertResponseRedirects('/filesystem?path=' . $parentDir);

        // assert directory was deleted
        $this->assertDirectoryDoesNotExist($tempDir);
    }

    /**
     * Test rename file form
     *
     * @return void
     */
    #[Group('file-system')]
    public function testRenameFileForm(): void
    {
        // create temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Test content');

        // request rename form
        $this->client->request('GET', '/filesystem/rename', [
            'path' => $tempFile
        ]);

        // assert response is successful
        $this->assertResponseIsSuccessful();

        // assert form exists
        $this->assertSelectorExists('form[action="/filesystem/rename/save"]');
        $this->assertSelectorExists('input[name="path"]');
        $this->assertSelectorExists('input[name="newName"]');
        $this->assertSelectorExists('button[type="submit"]');

        // clean up
        unlink($tempFile);
    }

    /**
     * Test rename file
     *
     * @return void
     */
    #[Group('file-system')]
    public function testRenameFile(): void
    {
        // create temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'Test content');

        // get directory path and new file path
        $directoryPath = dirname($tempFile);
        $newName = 'renamed_' . basename($tempFile);
        $newPath = $directoryPath . '/' . $newName;

        // submit form to rename file
        $this->client->request('POST', '/filesystem/rename/save', [
            'path' => $tempFile,
            'newName' => $newName
        ]);

        // assert redirect to directory
        $this->assertResponseRedirects('/filesystem?path=' . $directoryPath);

        // assert file was renamed
        $this->assertFileExists($newPath);
        $this->assertFileDoesNotExist($tempFile);

        // clean up
        unlink($newPath);
    }

    /**
     * Test rename directory
     *
     * @return void
     */
    #[Group('file-system')]
    public function testRenameDirectory(): void
    {
        // create temporary directory for testing
        $tempDir = sys_get_temp_dir() . '/test_' . uniqid();
        mkdir($tempDir);

        // get parent directory path and new directory path
        $parentDir = dirname($tempDir);
        $newName = 'renamed_' . basename($tempDir);
        $newPath = $parentDir . '/' . $newName;

        // submit form to rename directory
        $this->client->request('POST', '/filesystem/rename/save', [
            'path' => $tempDir,
            'newName' => $newName
        ]);

        // assert redirect to parent directory
        $this->assertResponseRedirects('/filesystem?path=' . $parentDir);

        // assert directory was renamed
        $this->assertDirectoryExists($newPath);
        $this->assertDirectoryDoesNotExist($tempDir);

        // clean up
        rmdir($newPath);
    }

    /**
     * Test load file upload page
     *
     * @return void
     */
    public function testLoadFileUploadPage(): void
    {
        $this->client->request('GET', '/filesystem/upload?path=/tmp');

        // assert response
        $this->assertSelectorTextContains('title', 'Admin suite');
        $this->assertSelectorTextContains('h1', 'Upload Files');
        $this->assertSelectorTextContains('body', 'Select Files to Upload');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
