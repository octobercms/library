<?php

/**
 * NestedTreeRestoreTest
 */
class NestedTreeRestoreTest extends TestCase
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

        // Wire a real container as the facade root so the facades resolve
        $this->savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        // Re-register model events against the dispatcher so the tree events fire
        TestModelNestedTreeRestore::flushEventListeners();

        // The restore events are registered once at boot, so the flush above removes them
        TestModelNestedTreeRestore::bootSoftDelete();

        $capsule->schema()->dropIfExists('test_nested_tree_restore');
        $capsule->schema()->create('test_nested_tree_restore', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
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
     * testRestoreAllocatesClosedSlot confirms a deleted node gets its slot and children back
     */
    public function testRestoreAllocatesClosedSlot()
    {
        $this->makeTree([
            [1, 'Parent', null, 1, 4],
            [2, 'Child', 1, 2, 3],
            [3, 'Sibling', null, 5, 6],
        ]);

        $tree = $this->getTree();

        TestModelNestedTreeRestore::find(1)->delete();
        $this->assertEquals([[3, null, 1, 2]], $this->getTree());

        TestModelNestedTreeRestore::withTrashed()->find(1)->restore();
        $this->assertEquals($tree, $this->getTree());
    }

    /**
     * testRestoreKeepsOpenSlot confirms a node trashed without its delete event returns to the slot its children still sit in
     */
    public function testRestoreKeepsOpenSlot()
    {
        $this->makeTree([
            [1, 'Parent', null, 1, 4],
            [2, 'Child', 1, 2, 3],
            [3, 'Sibling', null, 5, 6],
        ]);

        $tree = $this->getTree();

        $this->trashWithoutEvents(1);

        TestModelNestedTreeRestore::withTrashed()->find(1)->restore();
        $this->assertEquals($tree, $this->getTree());
    }

    /**
     * testRestoreAllocatesSlotWhenEdgesAreTaken confirms a closed slot is not reused because a child was restored on its own
     */
    public function testRestoreAllocatesSlotWhenEdgesAreTaken()
    {
        $this->makeTree([
            [1, 'Parent', null, 1, 6],
            [2, 'First Child', 1, 2, 3],
            [3, 'Second Child', 1, 4, 5],
            [4, 'Sibling', null, 7, 8],
        ]);

        TestModelNestedTreeRestore::find(1)->delete();
        TestModelNestedTreeRestore::withTrashed()->find(2)->restore();
        TestModelNestedTreeRestore::withTrashed()->find(1)->restore();

        $positions = [];
        foreach ($this->getTree() as [$id, $parentId, $left, $right]) {
            array_push($positions, $left, $right);
        }

        $this->assertCount(8, $positions);
        $this->assertEquals($positions, array_unique($positions));
    }

    /**
     * makeTree inserts nodes as their id, name, parent, left and right
     */
    protected function makeTree(array $nodes): void
    {
        foreach ($nodes as [$id, $name, $parentId, $left, $right]) {
            self::$capsule->getConnection()->table('test_nested_tree_restore')->insert([
                'id' => $id,
                'name' => $name,
                'parent_id' => $parentId,
                'nest_left' => $left,
                'nest_right' => $right,
                'nest_depth' => $parentId ? 1 : 0,
            ]);
        }
    }

    /**
     * trashWithoutEvents marks a node as trashed, leaving the tree as it is
     */
    protected function trashWithoutEvents(int $id): void
    {
        self::$capsule->getConnection()->table('test_nested_tree_restore')
            ->where('id', $id)
            ->update(['deleted_at' => '2020-01-01 00:00:00']);
    }

    /**
     * getTree returns the live nodes in tree order as their id, parent, left and right
     */
    protected function getTree(): array
    {
        return self::$capsule->getConnection()->table('test_nested_tree_restore')
            ->whereNull('deleted_at')
            ->orderBy('nest_left')
            ->get()
            ->map(fn ($row) => [(int) $row->id, $row->parent_id, (int) $row->nest_left, (int) $row->nest_right])
            ->all();
    }
}

/**
 * TestModelNestedTreeRestore example class
 */
class TestModelNestedTreeRestore extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\NestedTree;
    use \October\Rain\Database\Traits\SoftDelete;

    protected $table = 'test_nested_tree_restore';
}
