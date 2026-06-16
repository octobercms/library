<?php

require_once __DIR__ . '/HalcyonDbTestHarness.php';
require_once __DIR__ . '/Concerns/SetsUpHalcyonDb.php';

use October\Rain\Halcyon\Datasource\DbDatasource;

class DbDatasourceTest extends TestCase
{
    use SetsUpHalcyonDb;

    protected string $dirName = 'pages';

    protected string $fileName = 'home';

    protected string $extension = 'htm';

    public function setUp(): void
    {
        $this->setUpHalcyonDatabase();
    }

    public function tearDown(): void
    {
        $this->clearDbDatasourceCache();
    }

    public function testIsTemplateTrashedReturnsFalseForActiveRecord()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');

        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
    }

    public function testIsTemplateTrashedReturnsTrueAfterSoftDelete()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertTrue($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
    }

    public function testIsTemplateTrashedReturnsFalseWhenNoRecord()
    {
        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
    }

    public function testSelectTrashedFileNamesReturnsSoftDeletedTemplates()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->insert($this->dirName, 'about', $this->extension, '<p>About</p>');
        $this->dbDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $trashed = $this->dbDatasource->selectTrashedFileNames($this->dirName);

        $this->assertEquals(['home.htm'], $trashed);
    }

    public function testSelectTrashedFileNamesRespectsExtensionsFilter()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->insert('content', 'welcome', 'htm', '<p>Welcome</p>');
        $this->dbDatasource->insert('content', 'readme', 'md', '# Readme');
        $this->dbDatasource->delete($this->dirName, $this->fileName, $this->extension);
        $this->dbDatasource->delete('content', 'readme', 'md');

        $trashed = $this->dbDatasource->selectTrashedFileNames('content', ['extensions' => ['md']]);

        $this->assertEquals(['readme.md'], $trashed);
    }
}
