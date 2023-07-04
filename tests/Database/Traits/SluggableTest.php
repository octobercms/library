<?php

/**
 * SluggableTest
 */
class SluggableTest extends TestCase
{
    /**
     * setUp test
     */
    public function setUp(): void
    {
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);

        // Create the dataset in the connection with the tables
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('test_sluggable', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Mock app instance for this test
        App::swap(new class {
            public function getLocale() { return 'en'; }
        });
    }

    /**
     * testSlugGeneration
     */
    public function testSlugGeneration()
    {
        $testModel1 = TestModelSluggable::create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel2 = TestModelSluggable::create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test-2');

        $testModel3 = TestModelSluggable::create(['name' => 'test']);
        $this->assertEquals($testModel3->slug, 'test-3');
    }
}

/**
 * TestModelSluggable example class
 */
class TestModelSluggable extends Model
{
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'test_sluggable';
}
