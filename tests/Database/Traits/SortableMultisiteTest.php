<?php

/**
 * SortableMultisiteTest
 */
class SortableMultisiteTest extends TestCase
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
     * @var SortableMultisiteTestSiteManager sites resolved by the Site facade
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
        $this->sites = new SortableMultisiteTestSiteManager;

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->instance('system.sites', $this->sites);
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        // Re-register model events against the dispatcher so afterCreate fires
        TestModelSortableBeforeMultisite::flushEventListeners();
        TestModelMultisiteBeforeSortable::flushEventListeners();

        $capsule->schema()->dropIfExists('test_sortable_multisite');
        $capsule->schema()->create('test_sortable_multisite', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('site_id')->nullable();
            $table->integer('site_root_id')->nullable();
            $table->integer('sort_order')->nullable();
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
     * testSortableBeforeMultisiteSetsSortOrder confirms a new record is sorted while its site_root_id is still empty
     */
    public function testSortableBeforeMultisiteSetsSortOrder()
    {
        $first = TestModelSortableBeforeMultisite::create(['name' => 'First']);
        $second = TestModelSortableBeforeMultisite::create(['name' => 'Second']);

        $this->assertStoredOrder($first, $first->id, $first->id);
        $this->assertStoredOrder($second, $second->id, $second->id);
    }

    /**
     * testMultisiteBeforeSortableSetsSortOrder confirms a new record is sorted after its site_root_id is assigned
     */
    public function testMultisiteBeforeSortableSetsSortOrder()
    {
        $first = TestModelMultisiteBeforeSortable::create(['name' => 'First']);
        $second = TestModelMultisiteBeforeSortable::create(['name' => 'Second']);

        $this->assertStoredOrder($first, $first->id, $first->id);
        $this->assertStoredOrder($second, $second->id, $second->id);
    }

    /**
     * testSortableOrderMatchesSiteRootId confirms reordering existing records still matches the shared site_root_id
     */
    public function testSortableOrderMatchesSiteRootId()
    {
        $this->sites->siteId = 2;
        $root = TestModelSortableBeforeMultisite::create(['name' => 'Root']);

        $this->sites->siteId = 1;
        $other = TestModelSortableBeforeMultisite::create(['name' => 'Other', 'site_root_id' => $root->id]);

        (new TestModelSortableBeforeMultisite)->setSortableOrder([$root->id], [50]);

        $this->assertStoredOrder($other, 50, $root->id);
        $this->assertStoredOrder($root, $root->id, $root->id);
    }

    /**
     * assertStoredOrder checks the sort order and root key saved for a record
     */
    protected function assertStoredOrder(\October\Rain\Database\Model $model, int $sortOrder, int $siteRootId): void
    {
        $row = self::$capsule->getConnection()->table('test_sortable_multisite')->find($model->id);

        $this->assertEquals($sortOrder, $row->sort_order);
        $this->assertEquals($siteRootId, $row->site_root_id);
    }
}

/**
 * SortableMultisiteTestSiteManager resolves a fixed site context for the Site facade
 */
class SortableMultisiteTestSiteManager
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
}

/**
 * TestModelSortableBeforeMultisite example class
 */
class TestModelSortableBeforeMultisite extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sortable;
    use \October\Rain\Database\Traits\Multisite;

    protected $table = 'test_sortable_multisite';

    protected $fillable = ['name', 'site_root_id'];

    protected $propagatable = [];

    protected $propagatableSync = true;
}

/**
 * TestModelMultisiteBeforeSortable example class
 */
class TestModelMultisiteBeforeSortable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Multisite;
    use \October\Rain\Database\Traits\Sortable;

    protected $table = 'test_sortable_multisite';

    protected $fillable = ['name', 'site_root_id'];

    protected $propagatable = [];

    protected $propagatableSync = true;
}
