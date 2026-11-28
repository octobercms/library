<?php

/**
 * MultisiteDeleteTest
 */
class MultisiteDeleteTest extends TestCase
{
    /**
     * @var Illuminate\Database\Capsule\Manager capsule shared across tests so a single
     * :memory: connection is reused, keeping table drops and creates reliable
     */
    protected static $capsule;

    /**
     * @var mixed savedFacadeApp
     */
    protected $savedFacadeApp;

    /**
     * @var MultisiteDeleteTestSiteManager sites resolved by the Site facade
     */
    protected $sites;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        if (!self::$capsule) {
            self::$capsule = new Illuminate\Database\Capsule\Manager;
            self::$capsule->addConnection([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => ''
            ]);

            self::$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher);
        }

        $capsule = self::$capsule;
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Wire a real container as the facade root so the Site facade resolves
        $this->savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();
        $this->sites = new MultisiteDeleteTestSiteManager;

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->instance('system.sites', $this->sites);
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        // Re-register model events against the dispatcher so afterDelete fires
        TestModelMultisiteDelete::flushEventListeners();
        TestModelMultisiteSoftDelete::flushEventListeners();
        TestModelMultisiteSoftDeleteUnlinked::flushEventListeners();
        TestModelMultisiteDelete::$deleted = [];
        TestModelMultisiteSoftDelete::$deleted = [];
        TestModelMultisiteSoftDelete::$restored = [];

        // The restore events are registered once at boot, so the flush above removes them
        TestModelMultisiteSoftDelete::bootSoftDelete();
        TestModelMultisiteSoftDeleteUnlinked::bootSoftDelete();

        $capsule->schema()->dropIfExists('test_multisite_delete');
        $capsule->schema()->create('test_multisite_delete', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('site_id')->nullable();
            $table->integer('site_root_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Illuminate\Support\Facades\Facade::setFacadeApplication($this->savedFacadeApp);
    }

    /**
     * testDeleteRunsEventsInOtherSites confirms the records of the other sites are deleted with their events, each in its own site
     */
    public function testDeleteRunsEventsInOtherSites()
    {
        $root = $this->makeRecords(TestModelMultisiteDelete::class);

        $root->delete();

        $this->assertEquals(0, $this->countRows());
        $this->assertEquals([[2, 2], [3, 3], [1, 1]], TestModelMultisiteDelete::$deleted);
    }

    /**
     * testSoftDeleteRunsEventsInOtherSites confirms the records of the other sites are trashed with their events
     */
    public function testSoftDeleteRunsEventsInOtherSites()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDelete::class);

        $root->delete();

        $this->assertEquals(3, $this->countRows());
        $this->assertEquals(0, $this->countRows(false));
        $this->assertEquals([[2, 2], [3, 3], [1, 1]], TestModelMultisiteSoftDelete::$deleted);
    }

    /**
     * testSoftDeleteSkipsTrashedRecords confirms a record that is already in the trash is not deleted again
     */
    public function testSoftDeleteSkipsTrashedRecords()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDelete::class);

        self::$capsule->getConnection()->table('test_multisite_delete')
            ->where('site_id', 2)
            ->update(['deleted_at' => '2020-01-01 00:00:00']);

        $root->delete();

        $this->assertEquals(0, $this->countRows(false));
        $this->assertEquals([[3, 3], [1, 1]], TestModelMultisiteSoftDelete::$deleted);
        $this->assertEquals('2020-01-01 00:00:00', $root->findForSite(2)->deleted_at);
    }

    /**
     * testForceDeleteRemovesTrashedRecords confirms a force delete removes the records of the other sites from the trash
     */
    public function testForceDeleteRemovesTrashedRecords()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDelete::class);

        $root->delete();

        TestModelMultisiteSoftDelete::$deleted = [];

        $root->forceDelete();

        $this->assertEquals(0, $this->countRows());
        $this->assertEquals([[2, 2], [3, 3], [1, 1]], TestModelMultisiteSoftDelete::$deleted);
    }

    /**
     * testRestoreRunsEventsInOtherSites confirms the trashed records of the other sites are restored with their events, each in its own site
     */
    public function testRestoreRunsEventsInOtherSites()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDelete::class);

        $root->delete();
        $root->restore();

        $this->assertEquals(3, $this->countRows(false));
        $this->assertEquals([[2, 2], [3, 3], [1, 1]], TestModelMultisiteSoftDelete::$restored);
    }

    /**
     * testRestoreSkipsLiveRecords confirms a record that is not in the trash is not restored again
     */
    public function testRestoreSkipsLiveRecords()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDelete::class);

        $root->delete();

        self::$capsule->getConnection()->table('test_multisite_delete')
            ->where('site_id', 2)
            ->update(['deleted_at' => null]);

        $root->restore();

        $this->assertEquals(3, $this->countRows(false));
        $this->assertEquals([[3, 3], [1, 1]], TestModelMultisiteSoftDelete::$restored);
    }

    /**
     * testRestoreFollowsDeleteOption confirms the records of the other sites stay in the trash when the delete option is off
     */
    public function testRestoreFollowsDeleteOption()
    {
        $root = $this->makeRecords(TestModelMultisiteSoftDeleteUnlinked::class);

        self::$capsule->getConnection()->table('test_multisite_delete')
            ->update(['deleted_at' => '2020-01-01 00:00:00']);

        $root->findForSite(1)->restore();

        $this->assertEquals(1, $this->countRows(false));
        $this->assertEquals([[1, 1]], TestModelMultisiteSoftDelete::$restored);
    }

    /**
     * testSyncedRelationIsNotShared confirms a synced relation is deleted with its record while a shared relation waits for the last one
     */
    public function testSyncedRelationIsNotShared()
    {
        $root = $this->makeRecords(TestModelMultisiteDelete::class);

        $this->assertTrue($root->canDeleteMultisiteRelation('synced_items'));
        $this->assertFalse($root->canDeleteMultisiteRelation('shared_items'));
    }

    /**
     * testSiteScopedJoinIsNotShared confirms a join to a multisite model is deleted with its record while a join to a shared model waits for the last one
     */
    public function testSiteScopedJoinIsNotShared()
    {
        $root = $this->makeRecords(TestModelMultisiteDeleteJoins::class);

        $this->assertTrue($root->canDeleteMultisiteRelation('scoped_tags'));
        $this->assertFalse($root->canDeleteMultisiteRelation('shared_tags'));
    }

    /**
     * makeRecords creates a record in site 1 with a linked record in sites 2 and 3, returning the first
     */
    protected function makeRecords(string $class): \October\Rain\Database\Model
    {
        $root = $class::create(['name' => 'Root']);

        foreach ([2, 3] as $siteId) {
            $this->sites->withContext($siteId, function () use ($class, $root) {
                $class::create(['name' => 'Other', 'site_root_id' => $root->id]);
            });
        }

        return $root;
    }

    /**
     * countRows in the table, optionally leaving out the trashed rows
     */
    protected function countRows(bool $withTrashed = true): int
    {
        $query = self::$capsule->getConnection()->table('test_multisite_delete');

        if (!$withTrashed) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }
}

/**
 * MultisiteDeleteTestSiteManager resolves three sites in one group for the Site facade
 */
class MultisiteDeleteTestSiteManager
{
    /**
     * @var int siteId from the context
     */
    public $siteId = 1;

    /**
     * hasGlobalContext
     */
    public function hasGlobalContext()
    {
        return false;
    }

    /**
     * getSiteIdFromContext
     */
    public function getSiteIdFromContext()
    {
        return $this->siteId;
    }

    /**
     * listSiteIdsInGroup
     */
    public function listSiteIdsInGroup($siteId = null)
    {
        return [1, 2, 3];
    }

    /**
     * withContext
     */
    public function withContext($siteId, Closure $callback)
    {
        $previous = $this->siteId;

        $this->siteId = $siteId;

        try {
            return $callback();
        }
        finally {
            $this->siteId = $previous;
        }
    }
}

/**
 * TestModelMultisiteDelete example class
 */
class TestModelMultisiteDelete extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Multisite;

    /**
     * @var array deleted records, each as the record site and the site in context
     */
    public static $deleted = [];

    protected $table = 'test_multisite_delete';

    protected $fillable = ['name', 'site_root_id'];

    protected $propagatable = ['synced_items', 'shared_items'];

    protected $propagatableSync = true;

    public $hasMany = [
        'synced_items' => [TestModelMultisiteDelete::class, 'key' => 'site_root_id', 'multisiteSync' => true],
        'shared_items' => [TestModelMultisiteDelete::class, 'key' => 'site_root_id'],
    ];

    public function afterDelete()
    {
        static::$deleted[] = [(int) $this->site_id, (int) Site::getSiteIdFromContext()];
    }
}

/**
 * TestModelMultisiteDeleteJoins example class
 */
class TestModelMultisiteDeleteJoins extends TestModelMultisiteDelete
{
    protected $propagatable = ['scoped_tags', 'shared_tags'];

    public $belongsToMany = [
        'scoped_tags' => [TestModelMultisiteDelete::class, 'table' => 'test_multisite_delete_tags'],
        'shared_tags' => [TestModelMultisiteDeleteTag::class, 'table' => 'test_multisite_delete_tags'],
    ];
}

/**
 * TestModelMultisiteDeleteTag example class
 */
class TestModelMultisiteDeleteTag extends \October\Rain\Database\Model
{
    protected $table = 'test_multisite_delete';
}

/**
 * TestModelMultisiteSoftDelete example class
 */
class TestModelMultisiteSoftDelete extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Multisite;

    /**
     * @var array deleted records, each as the record site and the site in context
     */
    public static $deleted = [];

    protected $table = 'test_multisite_delete';

    protected $fillable = ['name', 'site_root_id'];

    /**
     * @var array restored records, each as the record site and the site in context
     */
    public static $restored = [];

    protected $propagatable = [];

    protected $propagatableSync = true;

    public function afterDelete()
    {
        static::$deleted[] = [(int) $this->site_id, (int) Site::getSiteIdFromContext()];
    }

    public function afterRestore()
    {
        static::$restored[] = [(int) $this->site_id, (int) Site::getSiteIdFromContext()];
    }
}

/**
 * TestModelMultisiteSoftDeleteUnlinked example class
 */
class TestModelMultisiteSoftDeleteUnlinked extends TestModelMultisiteSoftDelete
{
    protected $propagatableSync = ['sync' => 'group', 'delete' => false];
}
