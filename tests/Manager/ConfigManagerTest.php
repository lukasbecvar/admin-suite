<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Util\FileSystemUtil;
use App\Manager\ConfigManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ConfigManagerTest
 *
 * Test cases for configuration manager
 *
 * @package App\Tests\Manager
 */
class ConfigManagerTest extends TestCase
{
    private ConfigManager $configManager;
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private FileSystemUtil & MockObject $fileSystemUtilMock;

    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->fileSystemUtilMock = $this->createMock(FileSystemUtil::class);

        $this->configManager = new ConfigManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->fileSystemUtilMock
        );

        // mock app root directory
        $this->appUtilMock->method('getAppRootDir')->willReturn('/app');
    }

    /**
     * Test get suite configurations list
     *
     * @return void
     */
    public function testGetSuiteConfigs(): void
    {
        // mock config files list
        $defaultConfigPath = '/app/config/suite';
        $files = [
            ['name' => 'config1.json'],
            ['name' => 'config2.json']
        ];

        // mock get files list
        $this->fileSystemUtilMock->method('getFilesList')->with($defaultConfigPath)->willReturn($files);

        // mock custom file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->willReturnMap([
            ['/app/config1.json', false],
            ['/app/config2.json', true]
        ]);

        // assert result
        $this->assertEquals([
            ['filename' => 'config1.json', 'is_custom' => false],
            ['filename' => 'config2.json', 'is_custom' => true]
        ], $this->configManager->getSuiteConfigs());
    }

    /**
     * Test read suite configuration file
     *
     * @return void
     */
    public function testReadConfigWhenCustomFileExists(): void
    {
        // mock config data
        $filename = 'test.json';
        $customPath = '/app/test.json';
        $content = '{"key":"value"}';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($customPath)->willReturn(true);

        // mock config file content
        $this->fileSystemUtilMock->method('getFullFileContent')->with($customPath)->willReturn($content);

        // assert result
        $this->assertEquals($content, $this->configManager->readConfig($filename));
    }

    /**
     * Test read suite configuration file when only default file exists
     *
     * @return void
     */
    public function testReadConfigWhenOnlyDefaultFileExists(): void
    {
        // mock config data
        $filename = 'test.json';
        $customPath = '/app/test.json';
        $defaultPath = '/app/config/suite/test.json';
        $content = '{"key":"default"}';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->willReturnMap([
            [$customPath, false],
            [$defaultPath, true]
        ]);

        // mock config file content
        $this->fileSystemUtilMock->method('getFullFileContent')->with($defaultPath)->willReturn($content);

        // assert result
        $this->assertEquals($content, $this->configManager->readConfig($filename));
    }

    /**
     * Test read suite configuration file when file does not exist
     *
     * @return void
     */
    public function testReadConfigWhenFileDoesNotExist(): void
    {
        // mock config data
        $filename = 'test.json';
        $customPath = '/app/test.json';
        $defaultPath = '/app/config/suite/test.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->willReturnMap([
            [$customPath, false],
            [$defaultPath, false]
        ]);

        // assert result
        $this->assertNull($this->configManager->readConfig($filename));
    }

    /**
     * Test write suite configuration file success
     *
     * @return void
     */
    public function testWriteConfigSuccess(): void
    {
        // mock config data
        $filename = 'test.json';
        $content = '{"foo":"bar"}';
        $path = '/app/test.json';

        // mock file write
        $this->fileSystemUtilMock->method('saveFileContent')->with($path, $content)->willReturn(true);

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log');

        // assert result
        $this->assertTrue($this->configManager->writeConfig($filename, $content));
    }

    /**
     * Test write suite configuration file failure
     *
     * @return void
     */
    public function testWriteConfigFailure(): void
    {
        // mock config data
        $filename = 'test.json';
        $content = '{"foo":"bar"}';
        $path = '/app/test.json';

        // mock file write
        $this->fileSystemUtilMock->method('saveFileContent')->with($path, $content)->willReturn(false);

        // expect log method not called
        $this->logManagerMock->expects($this->never())->method('log');

        // assert result
        $this->assertFalse($this->configManager->writeConfig($filename, $content));
    }

    /**
     * Test copy suite configuration file to root directory success
     *
     * @return void
     */
    public function testCopyConfigToRootSuccess(): void
    {
        // mock config data
        $filename = 'new.json';
        $sourcePath = '/app/config/suite/new.json';
        $destinationPath = '/app/new.json';
        $content = '{"data":"new"}';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->willReturnMap([
            [$sourcePath, true],
            [$destinationPath, false]
        ]);

        // mock config file content
        $this->fileSystemUtilMock->method('getFullFileContent')->with($sourcePath)->willReturn($content);

        // mock file write
        $this->fileSystemUtilMock->method('saveFileContent')->with($destinationPath, $content)->willReturn(true);

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log');

        // assert result
        $this->assertTrue($this->configManager->copyConfigToRoot($filename));
    }

    /**
     * Test copy suite configuration file to root directory when source does not exist
     *
     * @return void
     */
    public function testCopyConfigToRootWhenSourceDoesNotExist(): void
    {
        // mock config data
        $filename = 'new.json';
        $sourcePath = '/app/config/suite/new.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($sourcePath)->willReturn(false);

        // assert result
        $this->assertFalse($this->configManager->copyConfigToRoot($filename));
    }

    /**
     * Test copy suite configuration file to root directory when destination exists
     *
     * @return void
     */
    public function testCopyConfigToRootWhenDestinationExists(): void
    {
        // mock config data
        $filename = 'new.json';
        $sourcePath = '/app/config/suite/new.json';
        $destinationPath = '/app/new.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->willReturnMap([
            [$sourcePath, true],
            [$destinationPath, true]
        ]);

        // assert result
        $this->assertFalse($this->configManager->copyConfigToRoot($filename));
    }

    /**
     * Test check if suite configuration file is a custom file
     *
     * @return void
     */
    public function testIsCustomConfigWhenFileExists(): void
    {
        // mock config data
        $filename = 'custom.json';
        $path = '/app/custom.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($path)->willReturn(true);

        // assert result
        $this->assertTrue($this->configManager->isCustomConfig($filename));
    }

    /**
     * Test check if suite configuration file is a custom file when file does not exist
     *
     * @return void
     */
    public function testIsCustomConfigWhenFileDoesNotExist(): void
    {
        // mock config data
        $filename = 'custom.json';
        $path = '/app/custom.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($path)->willReturn(false);

        // assert result
        $this->assertFalse($this->configManager->isCustomConfig($filename));
    }

    /**
     * Test delete suite configuration file success
     *
     * @return void
     */
    public function testDeleteConfigSuccess(): void
    {
        // mock config data
        $filename = 'custom.json';
        $path = '/app/custom.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($path)->willReturn(true);

        // mock file delete
        $this->fileSystemUtilMock->method('deleteFileOrDirectory')->with($path)->willReturn(true);

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log');

        // assert result
        $this->assertTrue($this->configManager->deleteConfig($filename));
    }

    /**
     * Test delete suite configuration file when file does not exist
     *
     * @return void
     */
    public function testDeleteConfigWhenFileDoesNotExist(): void
    {
        // mock config data
        $filename = 'custom.json';
        $path = '/app/custom.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($path)->willReturn(false);

        // assert result
        $this->assertFalse($this->configManager->deleteConfig($filename));
    }

    /**
     * Test delete suite configuration file failure
     *
     * @return void
     */
    public function testDeleteConfigFailure(): void
    {
        // mock config data
        $filename = 'custom.json';
        $path = '/app/custom.json';

        // mock file exist check
        $this->fileSystemUtilMock->method('checkIfFileExist')->with($path)->willReturn(true);

        // mock file delete
        $this->fileSystemUtilMock->method('deleteFileOrDirectory')->with($path)->willReturn(false);

        // expect log method not called
        $this->logManagerMock->expects($this->never())->method('log');

        // assert result
        $this->assertFalse($this->configManager->deleteConfig($filename));
    }
}
