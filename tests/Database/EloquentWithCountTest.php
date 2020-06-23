<?php

class EloquentWithCountTest extends TestCase
{
    public function setUp()
    {
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        # Create the dataset in the connection with the tables
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('one', static function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $capsule->schema()->create('two', static function ($table) {
            $table->increments('id');
            $table->integer('one_id');
            $table->timestamps();
        });

        $capsule->schema()->create('three', static function ($table) {
            $table->increments('id');
            $table->integer('two_id');
            $table->timestamps();
        });

        $capsule->schema()->create('four', static function ($table) {
            $table->increments('id');
            $table->integer('one_id');
        });
    }

    public function testItBasic()
    {
        $one = Model1::create();
        $two = $one->twos()->Create();
        $two->threes()->Create();

        $results = Model1::query()->select(['id'])->withCount([
            'twos' => static function ($query) {
                $query->where('id', '>=', 1);
            },
        ]);

        $this->assertEquals([
            ['id' => 1, 'twos_count' => 1],
        ], $results->get()->toArray());
    }

    public function testGlobalScopes()
    {
        $one = Model1::create();
        $one->fours()->create();

        $result = Model1::query()->withCount('fours')->first();
        $this->assertEquals(0, $result->fours_count);
    }

    public function testSortingScopes()
    {
        $one = Model1::create();
        $one->twos()->create();

        $result = Model1::query()->withCount('twos')->toSql();

        $this->assertSame(
            'select "one".*, (select count(*) from "two" where "one"."id" = "two"."one_id") as "twos_count" from "one"',
            $result
        );
    }
}

class Model1 extends \October\Rain\Database\Model
{
    protected $table = 'one';
    public $timestamps = false;
    protected $guarded = ['id'];

    public $hasMany = [
        'twos' => [Model2::class, 'key' => 'one_id'],
        'fours' => [Model4::class, 'key' => 'one_id'],
    ];
}

class Model2 extends \October\Rain\Database\Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = ['id'];

    public $hasMany = [
        'threes' => [Model3::class, 'key' => 'two_id']
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', static function ($builder) {
            $builder->latest();
        });
    }
}

class Model3 extends \October\Rain\Database\Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', static function ($builder) {
            $builder->where('idz', '>', 0);
        });
    }
}

class Model4 extends \October\Rain\Database\Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', static function ($builder) {
            $builder->where('id', '>', 1);
        });
    }
}
