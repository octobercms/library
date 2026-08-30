<?php

require_once __DIR__ . '/HalcyonDbTestHarness.php';
require_once __DIR__ . '/Concerns/SetsUpHalcyonDb.php';

use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Datasource\AutoDatasource;
use October\Rain\Halcyon\Datasource\FileDatasource;

class CountingFilesystem extends Filesystem
{
    public array $reads = [];

    public function get($path, $lock = false)
    {
        $this->reads[] = $path;

        return parent::get($path, $lock);
    }
}

class AutoDatasourceTest extends TestCase
{
    use SetsUpHalcyonDb;

    protected AutoDatasource $autoDatasource;

    protected FileDatasource $fileDatasource;

    protected string $themePath;

    protected string $dirName = 'pages';

    protected string $fileName = 'home';

    protected string $extension = 'htm';

    public function setUp(): void
    {
        $this->setUpHalcyonDatabase();

        $this->themePath = sys_get_temp_dir() . '/halcyon-autodatasource-test-' . uniqid();
        mkdir($this->themePath . '/pages', 0755, true);

        $fixturePath = realpath(__DIR__ . '/../../fixtures/halcyon/themes/theme1/pages/home.htm');
        copy($fixturePath, $this->themePath . '/pages/home.htm');

        $this->fileDatasource = new FileDatasource($this->themePath, new Filesystem);
        $this->autoDatasource = new AutoDatasource([$this->dbDatasource, $this->fileDatasource]);
    }

    public function tearDown(): void
    {
        $this->tearDownHalcyonDatabase();

        $files = new Filesystem;
        $files->deleteDirectory($this->themePath);
    }

    public function testSoftDeletedFilesystemOnlyTemplateIsHidden()
    {
        $this->assertTrue($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));

        $this->autoDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertFalse($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertNull($this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension));
        $this->assertTrue($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
        $this->assertFileExists($this->themePath . '/pages/home.htm');
    }

    public function testSoftDeletedTemplateDoesNotFallbackToFilesystem()
    {
        $fileContent = $this->fileDatasource->selectOne($this->dirName, $this->fileName, $this->extension);
        $this->assertNotNull($fileContent);
        $this->assertStringContainsString('World!', $fileContent['content']);

        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>Database override</p>');

        $dbContent = $this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension);
        $this->assertNotNull($dbContent);
        $this->assertStringContainsString('Database override', $dbContent['content']);

        $this->autoDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertFalse($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertNull($this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension));

        $templates = $this->autoDatasource->select($this->dirName, ['extensions' => ['htm']]);
        $this->assertArrayNotHasKey('home.htm', $templates);
        $this->assertFileExists($this->themePath . '/pages/home.htm');
    }

    public function testLastModifiedReturnsNullForTombstonedTemplate()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>Database override</p>');
        $this->autoDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertNull($this->autoDatasource->lastModified($this->dirName, $this->fileName, $this->extension));
    }

    public function testForceDeleteRemovesFromAllLayers()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>Database override</p>');

        $targetFile = $this->themePath . '/pages/home.htm';
        $this->assertFileExists($targetFile);

        $this->autoDatasource->forceDelete($this->dirName, $this->fileName, $this->extension);

        $this->assertFalse($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertFileNotExists($targetFile);
        $this->assertTrue($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
    }

    public function testInsertRestoresTrashedTemplate()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>Database override</p>');
        $this->autoDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertFalse($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));

        $this->autoDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>Restored content</p>');

        $this->assertTrue($this->autoDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));

        $content = $this->autoDatasource->selectOne($this->dirName, $this->fileName, $this->extension);
        $this->assertStringContainsString('Restored content', $content['content']);
    }

    public function testSelectOneReadsTemplateContentOnce()
    {
        $files = new CountingFilesystem;
        $auto = new AutoDatasource([new FileDatasource($this->themePath, $files)]);

        $result = $auto->selectOne($this->dirName, $this->fileName, $this->extension);

        $this->assertNotNull($result);
        $this->assertCount(1, $files->reads);
    }

    public function testSelectOneSkipsDatasourcesWithoutTheTemplate()
    {
        $files = new CountingFilesystem;
        $auto = new AutoDatasource([$this->dbDatasource, new FileDatasource($this->themePath, $files)]);

        $result = $auto->selectOne($this->dirName, $this->fileName, $this->extension);

        $this->assertNotNull($result);
        $this->assertCount(1, $files->reads);
        $this->assertNull($auto->selectOne($this->dirName, 'missing', $this->extension));
    }

    public function testLastModifiedDoesNotReadTemplateContent()
    {
        $files = new CountingFilesystem;
        $auto = new AutoDatasource([new FileDatasource($this->themePath, $files)]);

        $this->assertIsInt($auto->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertNull($auto->lastModified($this->dirName, 'missing', $this->extension));
        $this->assertCount(0, $files->reads);
    }

    public function testHasTemplateDoesNotReadTemplateContent()
    {
        $files = new CountingFilesystem;
        $fileDatasource = new FileDatasource($this->themePath, $files);

        $this->assertTrue($fileDatasource->hasTemplate($this->dirName, $this->fileName, $this->extension));
        $this->assertFalse($fileDatasource->hasTemplate($this->dirName, 'missing', $this->extension));
        $this->assertCount(0, $files->reads);
    }
}
