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
        $this->tearDownHalcyonDatabase();
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

    public function testSelectDoesNotLeakAcrossSiblingDirectoriesWithSharedPrefix()
    {
        $this->dbDatasource->insert('content/static-pages', 'about', 'htm', '<p>About</p>');
        $this->dbDatasource->insert('content/static-pages-fr', 'about', 'htm', '<p>A propos</p>');

        $results = $this->dbDatasource->select('content/static-pages');

        $fileNames = array_column($results, 'fileName');
        sort($fileNames);

        $this->assertEquals(['about.htm'], $fileNames);
    }

    public function testSelectReturnsOnlyDirectoryEntriesWhenSiblingPrefixExists()
    {
        $this->dbDatasource->insert('content/static-pages-fr', 'about', 'htm', '<p>A propos</p>');

        $results = $this->dbDatasource->select('content/static-pages');

        $this->assertEquals([], $results);
    }

    public function testSelectTrashedFileNamesDoesNotLeakAcrossSiblingDirectoriesWithSharedPrefix()
    {
        $this->dbDatasource->insert('content/static-pages', 'about', 'htm', '<p>About</p>');
        $this->dbDatasource->insert('content/static-pages-fr', 'about', 'htm', '<p>A propos</p>');
        $this->dbDatasource->delete('content/static-pages', 'about', 'htm');
        $this->dbDatasource->delete('content/static-pages-fr', 'about', 'htm');

        $trashed = $this->dbDatasource->selectTrashedFileNames('content/static-pages');

        $this->assertEquals(['about.htm'], $trashed);
    }

    public function testSelectReturnsFileNameWithoutLeadingDashWhenSiblingDirectoryExists()
    {
        // Regression: a bare LIKE '{dirName}%' matched sibling paths and
        // pathToFileName() stripped only the dirName, leaving fragments
        // like "-fr/about.htm" that downstream loaders could not resolve,
        // producing null CmsObjects (see RainLab\Pages\Classes\PageList::getPageTree).
        $this->dbDatasource->insert('content/static-pages-fr', 'about', 'htm', '<p>A propos</p>');

        $results = $this->dbDatasource->select('content/static-pages');

        foreach ($results as $item) {
            $this->assertStringStartsNotWith('-', $item['fileName']);
        }
    }

    public function testScopedIndexesDoNotLeakAcrossRequestsOrReadUnscopedCache()
    {
        Db::getSchemaBuilder()->table($this->dbTable, function ($table) {
            $table->integer('site_id');
        });
        foreach ([1, 2] as $siteId) {
            Db::table($this->dbTable)->insert([
                'source' => 'test-theme',
                'path' => 'pages/home.htm',
                'content' => 'Site '.$siteId,
                'updated_at' => '2026-09-01 00:00:00',
                'deleted_at' => $siteId === 1 ? '2026-09-01 00:00:00' : null,
                'site_id' => $siteId,
            ]);
        }

        $siteId = 1;
        $this->dbDatasource->bindEvent('halcyon.datasource.db.extendQuery', function ($query) use (&$siteId) {
            $query->where('site_id', $siteId);
        });
        $this->assertTrue($this->dbDatasource->isTemplateTrashed('pages', 'home', 'htm'));
        $this->assertNull($this->dbDatasource->lastModified('pages', 'home', 'htm'));

        // A second request keeps the application cache, but resets request indexes.
        $this->clearDbDatasourceCache();
        $siteId = 2;
        $this->assertFalse($this->dbDatasource->isTemplateTrashed('pages', 'home', 'htm'));
        $this->assertSame(Carbon\Carbon::parse('2026-09-01 00:00:00')->timestamp, $this->dbDatasource->lastModified('pages', 'home', 'htm'));

        // An existing unscoped cache entry must also be ignored by scoped readers.
        $unscoped = new DbDatasource('test-theme', $this->dbTable);
        $this->assertTrue($unscoped->isTemplateTrashed('pages', 'home', 'htm'));
        $this->clearDbDatasourceCache();
        $this->assertFalse($this->dbDatasource->isTemplateTrashed('pages', 'home', 'htm'));
    }

    public function testOverriddenQueriesDoNotReuseUnscopedIndexes()
    {
        $this->dbDatasource->insert('pages', 'home', 'htm', 'Visible');
        $this->assertNotNull($this->dbDatasource->lastModified('pages', 'home', 'htm'));

        $scoped = new class('test-theme', $this->dbTable) extends DbDatasource {
            protected function getBaseQuery()
            {
                return parent::getBaseQuery()->where('path', 'pages/other.htm');
            }
        };
        $this->assertNull($scoped->lastModified('pages', 'home', 'htm'));
        $this->clearDbDatasourceCache();
        $this->assertNull($scoped->lastModified('pages', 'home', 'htm'));
    }

    public function testConcurrentInvalidationRejectsAnOlderSnapshot()
    {
        Db::table($this->dbTable)->insert([
            'source' => 'test-theme',
            'path' => 'pages/home.htm',
            'content' => 'Before',
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $connection = Db::connection();
        $previousDispatcher = $connection->getEventDispatcher();
        $connection->setEventDispatcher(new Illuminate\Events\Dispatcher);
        $interleaved = false;
        $writer = new DbDatasource('test-theme', $this->dbTable);
        $connection->listen(function ($event) use ($writer, &$interleaved) {
            if (!$interleaved && str_starts_with($event->sql, 'select "updated_at"')) {
                $interleaved = true;
                $writer->update('pages', 'home', 'htm', 'After');
            }
        });

        try {
            $this->dbDatasource->lastModified('pages', 'home', 'htm');
            $this->assertTrue($interleaved);
            $this->clearDbDatasourceCache();
            $expected = Carbon\Carbon::parse(Db::table($this->dbTable)->value('updated_at'))->timestamp;
            $this->assertSame($expected, $this->dbDatasource->lastModified('pages', 'home', 'htm'));
        }
        finally {
            if ($previousDispatcher) {
                $connection->setEventDispatcher($previousDispatcher);
            }
            else {
                $connection->unsetEventDispatcher();
            }
        }
    }

    public function testMissingGenerationDoesNotReviveAnOldSnapshot()
    {
        $this->dbDatasource->insert('pages', 'home', 'htm', 'Before');
        $this->dbDatasource->lastModified('pages', 'home', 'htm');
        $key = 'halcyon.db.' . $this->dbTable . '.test-theme';

        // Cache stores may evict the generation independently of the snapshot.
        Cache::forget($key . '.generation');
        Db::table($this->dbTable)->update(['updated_at' => '2020-01-01 00:00:00']);
        $this->clearDbDatasourceCache();

        $this->assertSame(
            Carbon\Carbon::parse('2020-01-01 00:00:00')->timestamp,
            $this->dbDatasource->lastModified('pages', 'home', 'htm')
        );
    }

    public function testLastModifiedStoresIndexesInApplicationCache()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');

        $mtime = $this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension);

        $cached = Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme');

        $this->assertNotNull($mtime);
        $this->assertIsArray($cached);
        $this->assertArrayHasKey('pages/home.htm', $cached['mtime']);
        $this->assertSame([], $cached['trashed']);
    }

    public function testIndexCacheHitFillsStaticIndexesWithoutQuerying()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension);

        $this->clearDbDatasourceCache();

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();

        $mtime = $this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension);
        $trashed = $this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension);

        $this->assertNotNull($mtime);
        $this->assertFalse($trashed);
        $this->assertSame([], Db::connection()->getQueryLog());
    }

    public function testEmptyDatasourceIsCachedAndDoesNotQueryOnNextRequest()
    {
        $this->assertNull($this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));

        $cached = Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme');
        $this->assertSame([], $cached['mtime']);
        $this->assertSame([], $cached['trashed']);
        $this->assertIsString($cached['generation']);

        $this->clearDbDatasourceCache();

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();

        $this->assertNull($this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
        $this->assertSame([], Db::connection()->getQueryLog());
    }

    public function testLastModifiedDoesNotCacheFailedQueries()
    {
        $datasource = new DbDatasource('test-theme', 'missing_templates');

        $this->assertNull($datasource->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertNull(Cache::memo()->get('halcyon.db.missing_templates.test-theme'));
    }

    public function testWarmStaticIndexesDoNotReadCacheOrDatabase()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->assertNotNull($this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension));

        Cache::memo()->forever('halcyon.db.' . $this->dbTable . '.test-theme', [
            'mtime' => [],
            'trashed' => ['pages/home.htm' => true],
        ]);

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();

        $this->assertNotNull($this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertFalse($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));
        $this->assertSame([], Db::connection()->getQueryLog());
    }

    public function testFlushCacheForgetsApplicationCacheEntry()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension);

        $this->assertNotNull(Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme'));

        $this->dbDatasource->update($this->dirName, $this->fileName, $this->extension, '<p>Updated</p>');

        $this->assertNull(Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme'));
    }

    public function testClearCacheDropsStaticIndexesAndApplicationCache()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension);

        DbDatasource::clearCache('test-theme', $this->dbTable);

        $this->assertNull(Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme'));

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();

        $this->assertNotNull($this->dbDatasource->lastModified($this->dirName, $this->fileName, $this->extension));
        $this->assertNotEmpty(Db::connection()->getQueryLog());
    }

    public function testTrashedPathsAreStoredInApplicationCache()
    {
        $this->dbDatasource->insert($this->dirName, $this->fileName, $this->extension, '<p>DB content</p>');
        $this->dbDatasource->delete($this->dirName, $this->fileName, $this->extension);

        $this->assertTrue($this->dbDatasource->isTemplateTrashed($this->dirName, $this->fileName, $this->extension));

        $cached = Cache::memo()->get('halcyon.db.' . $this->dbTable . '.test-theme');

        $this->assertSame(['pages/home.htm' => true], $cached['trashed']);
        $this->assertSame([], $cached['mtime']);
    }
}
