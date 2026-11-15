<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Dongle;
use October\Rain\Database\Model;

/**
 * BelongsToManySortableDeferredTest covers reading a sortable belongsToMany relation through deferred bindings.
 */
class BelongsToManySortableDeferredTest extends TestCase
{
    /**
     * @var mixed savedFacadeApp is the facade root to restore after the test
     */
    protected $savedFacadeApp;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        $capsule = new Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setEventDispatcher(new Illuminate\Events\Dispatcher);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Wire a container as the facade root so the Db and DbDongle facades resolve
        $this->savedFacadeApp = Facade::getFacadeApplication();

        $app = new Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->singleton('db.dongle', fn () => new Dongle('sqlite', $capsule->getDatabaseManager()));
        $app->instance('app', $app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        $capsule->schema()->create('test_sortable_events', function ($table) {
            $table->increments('id');
        });

        $capsule->schema()->create('test_sortable_members', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        $capsule->schema()->create('test_sortable_event_member', function ($table) {
            $table->integer('event_id');
            $table->integer('member_id');
            $table->integer('sort_order')->nullable();
        });

        $capsule->schema()->create('deferred_bindings', function ($table) {
            $table->increments('id');
            $table->string('master_type');
            $table->string('master_field');
            $table->string('slave_type');
            $table->integer('slave_id');
            $table->mediumText('pivot_data')->nullable();
            $table->string('session_key');
            $table->boolean('is_bind')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->savedFacadeApp);
    }

    /**
     * testDeferredRecordsFollowTheirSortOrder confirms records added to an unsaved model come back in the order they were given
     */
    public function testDeferredRecordsFollowTheirSortOrder()
    {
        $sessionKey = 'test-sortable-session';
        [$first, $second, $third] = $this->makeMembers();

        $event = new TestSortableEvent;
        $event->sessionKey = $sessionKey;

        foreach ([$first, $second, $third] as $member) {
            $event->members()->add($member, $sessionKey);
        }

        $event->setSortableRelationOrder('members', [$third->id, $first->id, $second->id], true);

        $records = $event->members()->withDeferred($sessionKey)->get();

        $this->assertSame(['Third', 'First', 'Second'], $records->pluck('name')->all());
        $this->assertSame(3, $event->members()->withDeferred($sessionKey)->count());
    }

    /**
     * testSavedOrderIsKeptThroughDeferredQuery confirms the pivot order of a saved model is kept when read with deferred bindings
     */
    public function testSavedOrderIsKeptThroughDeferredQuery()
    {
        [$first, $second, $third] = $this->makeMembers();

        $event = TestSortableEvent::create();
        $event->members()->attach([$first->id, $second->id, $third->id]);

        $event->setSortableRelationOrder('members', [$second->id, $third->id, $first->id], true);

        $records = $event->members()->withDeferred('test-other-session')->get();

        $this->assertSame(['Second', 'Third', 'First'], $records->pluck('name')->all());
    }

    /**
     * makeMembers creates three members to relate
     */
    protected function makeMembers(): array
    {
        return array_map(function ($name) {
            return TestSortableMember::create(['name' => $name]);
        }, ['First', 'Second', 'Third']);
    }
}

/**
 * TestSortableEvent example class
 */
class TestSortableEvent extends Model
{
    use \October\Rain\Database\Traits\SortableRelation;

    protected $table = 'test_sortable_events';

    public $timestamps = false;

    public $belongsToMany = [
        'members' => [
            TestSortableMember::class,
            'table' => 'test_sortable_event_member',
            'key' => 'event_id',
            'otherKey' => 'member_id',
            'pivotSortable' => 'sort_order',
        ],
    ];
}

/**
 * TestSortableMember example class
 */
class TestSortableMember extends Model
{
    protected $table = 'test_sortable_members';

    protected $fillable = ['name'];

    public $timestamps = false;
}
