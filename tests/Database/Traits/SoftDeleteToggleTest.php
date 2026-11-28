<?php

/**
 * SoftDeleteToggleTest
 */
class SoftDeleteToggleTest extends TestCase
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
        TestModelSoftDeleteToggle::flushEventListeners();
        TestModelSoftDeleteToggle::bootSoftDelete();
        TestModelSoftDeleteToggle::$softDeleteEnabled = true;

        $capsule->schema()->dropIfExists('test_soft_delete_toggle');
        $capsule->schema()->create('test_soft_delete_toggle', function ($table) {
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
     * testQueryDeleteTrashesWhenEnabled confirms a delete on the query keeps the rows in the trash
     */
    public function testQueryDeleteTrashesWhenEnabled()
    {
        $this->makeTree([
            [1, 'First', null, 1, 2],
            [2, 'Second', null, 3, 4],
        ]);

        TestModelSoftDeleteToggle::where('id', 1)->delete();

        $this->assertEquals([[2, null, 3, 4]], $this->getTree());
        $this->assertEquals(2, $this->countRows());
    }

    /**
     * testQueryDeleteRemovesWhenDisabled confirms a delete on the query removes the rows of a model that has no trash
     */
    public function testQueryDeleteRemovesWhenDisabled()
    {
        TestModelSoftDeleteToggle::$softDeleteEnabled = false;

        $this->makeTree([
            [1, 'First', null, 1, 2],
            [2, 'Second', null, 3, 4],
        ]);

        TestModelSoftDeleteToggle::where('id', 1)->delete();

        $this->assertEquals([[2, null, 3, 4]], $this->getTree());
        $this->assertEquals(1, $this->countRows());
    }

    /**
     * testDeleteRemovesDescendantsWhenDisabled confirms deleting a node removes its branch when the model has no trash
     */
    public function testDeleteRemovesDescendantsWhenDisabled()
    {
        TestModelSoftDeleteToggle::$softDeleteEnabled = false;

        $this->makeTree([
            [1, 'Parent', null, 1, 6],
            [2, 'Child', 1, 2, 5],
            [3, 'Grandchild', 2, 3, 4],
            [4, 'Sibling', null, 7, 8],
        ]);

        TestModelSoftDeleteToggle::find(1)->delete();

        $this->assertEquals([[4, null, 1, 2]], $this->getTree());
        $this->assertEquals(1, $this->countRows());
    }

    /**
     * testDeleteTrashesDescendantsWhenEnabled confirms deleting a node keeps its branch in the trash
     */
    public function testDeleteTrashesDescendantsWhenEnabled()
    {
        $this->makeTree([
            [1, 'Parent', null, 1, 6],
            [2, 'Child', 1, 2, 5],
            [3, 'Grandchild', 2, 3, 4],
            [4, 'Sibling', null, 7, 8],
        ]);

        TestModelSoftDeleteToggle::find(1)->delete();

        $this->assertEquals([[4, null, 1, 2]], $this->getTree());
        $this->assertEquals(4, $this->countRows());
    }

    /**
     * makeTree inserts nodes as their id, name, parent, left and right
     */
    protected function makeTree(array $nodes): void
    {
        foreach ($nodes as [$id, $name, $parentId, $left, $right]) {
            self::$capsule->getConnection()->table('test_soft_delete_toggle')->insert([
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
     * getTree returns the live nodes in tree order as their id, parent, left and right
     */
    protected function getTree(): array
    {
        return self::$capsule->getConnection()->table('test_soft_delete_toggle')
            ->whereNull('deleted_at')
            ->orderBy('nest_left')
            ->get()
            ->map(fn ($row) => [(int) $row->id, $row->parent_id, (int) $row->nest_left, (int) $row->nest_right])
            ->all();
    }

    /**
     * countRows returns the number of rows in the table, including the trash
     */
    protected function countRows(): int
    {
        return self::$capsule->getConnection()->table('test_soft_delete_toggle')->count();
    }
}

/**
 * TestModelSoftDeleteToggle example class
 */
class TestModelSoftDeleteToggle extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\NestedTree;
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var bool softDeleteEnabled switches the trash on and off
     */
    public static $softDeleteEnabled = true;

    protected $table = 'test_soft_delete_toggle';

    /**
     * isSoftDeleteEnabled
     */
    public function isSoftDeleteEnabled()
    {
        return static::$softDeleteEnabled;
    }
}
