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
    /**
     * testSlugGenerationFillsTheLowestFreeCounter
     */
    public function testSlugGenerationFillsTheLowestFreeCounter()
    {
        TestModelSluggable::create(['name' => 'test']);
        TestModelSluggable::create(['name' => 'test']);

        // Rows written outside the trait, a gap at 3, a non numeric suffix and
        // an unrelated slug that shares the prefix
        TestModelSluggable::insert([
            ['name' => 'test', 'slug' => 'test-4'],
            ['name' => 'test', 'slug' => 'test-foo'],
            ['name' => 'test', 'slug' => 'test-10'],
            ['name' => 'testing', 'slug' => 'testing'],
        ]);

        $this->assertEquals('test-3', TestModelSluggable::create(['name' => 'test'])->slug);
        $this->assertEquals('test-5', TestModelSluggable::create(['name' => 'test'])->slug);
        $this->assertEquals('testing-2', TestModelSluggable::create(['name' => 'testing'])->slug);
    }

    /**
     * testSlugGenerationUsesOneQueryRegardlessOfExistingCount
     */
    public function testSlugGenerationUsesOneQueryRegardlessOfExistingCount()
    {
        $rows = [['name' => 'test', 'slug' => 'test']];
        for ($i = 2; $i <= 200; $i++) {
            $rows[] = ['name' => 'test', 'slug' => 'test-' . $i];
        }
        TestModelSluggable::insert($rows);

        $connection = TestModelSluggable::resolveConnection();
        $connection->enableQueryLog();

        $model = TestModelSluggable::create(['name' => 'test']);

        $selects = array_filter($connection->getQueryLog(), function ($query) {
            return stripos($query['query'], 'select') === 0;
        });

        $this->assertEquals('test-201', $model->slug);
        $this->assertCount(1, $selects);
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
