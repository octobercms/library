<?php

class SluggableTest extends TestCase
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

        $capsule->schema()->create('testSoftDelete', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->softDeletes();
            $table->timestamps();
        });
        $capsule->schema()->create('test', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function testSlugGeneration()
    {
        /*
        * Basic usage of slug Generator
        */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel2->slug, 'test-2');

        $testModel1 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel2 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test-2');
    }

    public function testSlugGenerationWithSoftDelete()
    {
        /*
        * Slug Generation when identical key is softDeleted
        */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel1->delete();
        $this->assertNotNull($testSoftModel1->deleted_at);

        $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel2->slug, 'test-2');
    }

    public function testSlugGenerationWithHardDelete()
    {
        /*
        * Slug Generation when identical key was hardDeleted
        */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel1->forceDelete();

        $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel2->slug, 'test');

        $testModel1 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel1->delete();

        $testModel2 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test');
    }
}

/*
* Class with Sluggable and SoftDelete traits
*/
class TestModelSluggableSoftDelete extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $dates = ['deleted_at'];
    protected $table = 'testSoftDelete';

}

/*
* Class with only Sluggable trait
*/
class TestModelSluggable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $dates = ['deleted_at'];
    protected $table = 'test';

}
