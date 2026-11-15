<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\MissingAttributeException;
use October\Rain\Database\Model;

/**
 * ModelLaravelCompatTest
 */
class ModelLaravelCompatTest extends TestCase
{
    /**
     * setUp test
     */
    public function setUp(): void
    {
        $capsule = new Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('test_compat_entries', function ($table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
        });
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Model::preventAccessingMissingAttributes(false);
        TestCompatEntry::flushEventListeners();
    }

    /**
     * testRetrievedEventFires confirms the Laravel retrieved event fires next to fetched
     */
    public function testRetrievedEventFires()
    {
        TestCompatEntry::create(['name' => 'First']);

        $retrieved = [];
        TestCompatEntry::retrieved(function ($model) use (&$retrieved) {
            $retrieved[] = $model->name;
        });

        TestCompatEntry::all();

        $this->assertSame(['First'], $retrieved);
    }

    /**
     * testObserverReceivesRetrieved confirms an observer class is called for the retrieved event
     */
    public function testObserverReceivesRetrieved()
    {
        TestCompatEntry::create(['name' => 'First']);

        TestCompatObserver::$retrieved = [];
        TestCompatEntry::observe(TestCompatObserver::class);

        TestCompatEntry::all();

        $this->assertSame(['First'], TestCompatObserver::$retrieved);
    }

    /**
     * testMissingAttributeThrowsWhenPrevented confirms strict attribute access applies to Rain models
     */
    public function testMissingAttributeThrowsWhenPrevented()
    {
        $model = TestCompatEntry::find(TestCompatEntry::create(['name' => 'First'])->id);
        $this->assertNull($model->missing);
        $this->assertNull($model->parent);

        Model::preventAccessingMissingAttributes();

        $this->assertSame('First', $model->name);
        $this->assertNull($model->parent);

        $this->expectException(MissingAttributeException::class);
        $model->missing;
    }

    /**
     * testPushStopsOnCircularRelations confirms models that reference each other are pushed once
     */
    public function testPushStopsOnCircularRelations()
    {
        $parent = TestCompatEntry::create(['name' => 'Parent']);
        $child = TestCompatEntry::create(['name' => 'Child', 'parent_id' => $parent->id]);

        $parent->setRelation('children', $parent->newCollection([$child]));
        $child->setRelation('parent', $parent);
        $child->name = 'Changed';

        $this->assertTrue($parent->push());
        $this->assertSame('Changed', TestCompatEntry::find($child->id)->name);
    }
}

/**
 * TestCompatObserver example class
 */
class TestCompatObserver
{
    public static $retrieved = [];

    public function retrieved($model)
    {
        static::$retrieved[] = $model->name;
    }
}

/**
 * TestCompatEntry example class
 */
class TestCompatEntry extends Model
{
    protected $table = 'test_compat_entries';

    protected $fillable = ['name', 'parent_id'];

    public $timestamps = false;

    public $belongsTo = [
        'parent' => [TestCompatEntry::class, 'key' => 'parent_id'],
    ];

    public $hasMany = [
        'children' => [TestCompatEntry::class, 'key' => 'parent_id'],
    ];
}
