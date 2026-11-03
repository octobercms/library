<?php

/**
 * HasOneThroughTest
 */
class HasOneThroughTest extends TestCase
{
    /**
     * @var Illuminate\Database\Capsule\Manager capsule shared across tests so a single
     * :memory: connection is reused, keeping table drops and creates reliable
     */
    protected static $capsule;

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
        }

        $capsule = self::$capsule;
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        foreach (['test_through_suppliers', 'test_through_users', 'test_through_histories'] as $table) {
            $capsule->schema()->dropIfExists($table);
        }

        $capsule->schema()->create('test_through_suppliers', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        $capsule->schema()->create('test_through_users', function ($table) {
            $table->increments('id');
            $table->integer('supplier_id')->nullable();
        });

        $capsule->schema()->create('test_through_histories', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(false);
        });
    }

    /**
     * testConditionsAndOrderApply confirms the conditions and order definitions filter and sort the related record
     */
    public function testConditionsAndOrderApply()
    {
        $supplier = $this->makeSupplierWithHistory();

        $sql = $supplier->latestActiveHistory()->toSql();
        $this->assertStringContainsString('is_active = 1', $sql);
        $this->assertStringContainsString('order by "test_through_histories"."id" desc', $sql);

        $this->assertSame('Third', $supplier->latestActiveHistory->title);
        $this->assertSame('Third', TestThroughSupplier::with('latestActiveHistory')->find($supplier->id)->latestActiveHistory->title);
    }

    /**
     * testScopeApplies confirms the scope definition is applied to the related query
     */
    public function testScopeApplies()
    {
        $supplier = $this->makeSupplierWithHistory();

        $this->assertSame('Second', $supplier->inactiveHistory->title);
        $this->assertSame('Second', TestThroughSupplier::with('inactiveHistory')->find($supplier->id)->inactiveHistory->title);
    }

    /**
     * testDefaultApplies confirms the default definition supplies a model when nothing is found
     */
    public function testDefaultApplies()
    {
        $supplier = TestThroughSupplier::create(['name' => 'Empty']);

        $this->assertInstanceOf(TestThroughHistory::class, $supplier->historyOrDefault);
        $this->assertFalse($supplier->historyOrDefault->exists);
        $this->assertSame('No history', $supplier->historyOrDefault->title);
    }

    /**
     * makeSupplierWithHistory creates a supplier whose user has active and inactive history records
     */
    protected function makeSupplierWithHistory(): TestThroughSupplier
    {
        $supplier = TestThroughSupplier::create(['name' => 'Acme']);
        $user = TestThroughUser::create(['supplier_id' => $supplier->id]);

        foreach (['First' => true, 'Second' => false, 'Third' => true, 'Fourth' => false] as $title => $isActive) {
            TestThroughHistory::create(['user_id' => $user->id, 'title' => $title, 'is_active' => $isActive]);
        }

        return $supplier;
    }
}

/**
 * TestThroughSupplier example class
 */
class TestThroughSupplier extends \October\Rain\Database\Model
{
    protected $table = 'test_through_suppliers';

    protected $fillable = ['name'];

    public $timestamps = false;

    public $hasOneThrough = [
        'latestActiveHistory' => [
            TestThroughHistory::class,
            'through' => TestThroughUser::class,
            'key' => 'supplier_id',
            'throughKey' => 'user_id',
            'conditions' => 'is_active = 1',
            'order' => 'test_through_histories.id desc',
        ],
        'inactiveHistory' => [
            TestThroughHistory::class,
            'through' => TestThroughUser::class,
            'key' => 'supplier_id',
            'throughKey' => 'user_id',
            'scope' => 'onlyInactive',
        ],
        'historyOrDefault' => [
            TestThroughHistory::class,
            'through' => TestThroughUser::class,
            'key' => 'supplier_id',
            'throughKey' => 'user_id',
            'default' => ['title' => 'No history'],
        ],
    ];
}

/**
 * TestThroughUser example class
 */
class TestThroughUser extends \October\Rain\Database\Model
{
    protected $table = 'test_through_users';

    protected $fillable = ['supplier_id'];

    public $timestamps = false;
}

/**
 * TestThroughHistory example class
 */
class TestThroughHistory extends \October\Rain\Database\Model
{
    protected $table = 'test_through_histories';

    protected $fillable = ['user_id', 'title', 'is_active'];

    public $timestamps = false;

    /**
     * scopeOnlyInactive filters to inactive history records
     */
    public function scopeOnlyInactive($query)
    {
        return $query->where('is_active', false);
    }
}
