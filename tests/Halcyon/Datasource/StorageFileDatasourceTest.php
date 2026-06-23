<?php

require_once __DIR__ . '/HalcyonDbTestHarness.php';

use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Datasource\AutoDatasource;
use October\Rain\Halcyon\Datasource\FileDatasource;
use October\Rain\Halcyon\Datasource\StorageFileDatasource;

class StorageFileDatasourceTest extends TestCase
{
    protected StorageFileDatasource $storageDatasource;

    protected FileDatasource $fileDatasource;

    protected AutoDatasource $autoDatasource;

    protected string $themePath;

    protected string $storagePath;

    protected string $table = 'cms_theme_files_test';

    protected string $dirName = 'assets/images';

    protected string $fileName = 'logo';

    protected string $extension = 'png';

    public function setUp(): void
    {
        $this->bootStorageDatabase();

        $this->themePath = sys_get_temp_dir() . '/halcyon-storagefile-test-theme-' . uniqid();
        $this->storagePath = sys_get_temp_dir() . '/halcyon-storagefile-test-storage-' . uniqid();
        mkdir($this->themePath . '/assets/images', 0755, true);
        mkdir($this->storagePath, 0755, true);

        $this->storageDatasource = new StorageFileDatasource(
            'test-theme',
            $this->table,
            $this->storagePath,
            new Filesystem
        );

        $this->fileDatasource = new FileDatasource($this->themePath, new Filesystem);
        $this->autoDatasource = new AutoDatasource([$this->storageDatasource, $this->fileDatasource]);
    }

    public function tearDown(): void
    {
        HalcyonDbTestHarness::teardown();

        $files = new Filesystem;
        $files->deleteDirectory($this->themePath);
        $files->deleteDirectory($this->storagePath);
    }

    protected function bootStorageDatabase(): void
    {
        HalcyonDbTestHarness::bootStorageFilesTable($this->table);
    }

    public function testInsertStoresFileOnDiskAndInDatabase()
    {
        $content = 'binary-image-data';
        $this->storageDatasource->insert($this->dirName, $this->fileName, $this->extension, $content);

        $diskPath = $this->storagePath . '/assets/images/logo.png';
        $this->assertFileExists($diskPath);
        $this->assertSame($content, file_get_contents($diskPath));
        $this->assertTrue($this->storageDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
    }

    public function testAutoDatasourcePrefersStorageLayer()
    {
        $filesystemContent = 'filesystem-content';
        $storageContent = 'storage-content';

        file_put_contents($this->themePath . '/assets/images/logo.png', $filesystemContent);
        $this->storageDatasource->insert($this->dirName, $this->fileName, $this->extension, $storageContent);

        $result = $this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension);
        $this->assertSame($storageContent, $result['content']);
    }

    public function testAutoDatasourceFallsBackToFilesystem()
    {
        $filesystemContent = 'filesystem-content';
        file_put_contents($this->themePath . '/assets/images/logo.png', $filesystemContent);

        $result = $this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension);
        $this->assertNotNull($result);
        $this->assertSame($filesystemContent, $result['content']);
    }

    public function testSoftDeletedFilesystemFileIsHidden()
    {
        file_put_contents($this->themePath . '/assets/images/logo.png', 'filesystem-content');

        $this->assertTrue($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));

        $this->autoDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertFalse($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertTrue($this->storageDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
    }

    public function testResolveLocalPathReturnsStoragePath()
    {
        $this->storageDatasource->insert($this->dirName, $this->fileName, $this->extension, 'content');

        $localPath = $this->autoDatasource->resolveLocalPath($this->dirName, $this->fileName, $this->extension);
        $this->assertSame($this->storagePath . '/assets/images/logo.png', $localPath);
    }
}
