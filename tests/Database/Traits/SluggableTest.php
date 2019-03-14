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
    }

    public function testSlugGeneration()
    {
        /*
         * Basic usage of slug Generator
         */
        $testModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test-2');
    }

    public function testSlugGenerationWithSoftDelete()
    {
        /*
         * Slug Generation when identical key is softDeleted
         */
        $testModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel1->delete();
        $this->assertNotNull($testModel1->deleted_at);

        $testModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test-2');
    }

    public function testSlugGenerationWithHardDelete()
    {
        /*
         * Slug Generation when identical key was hardDeleted
         */
        $testModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel1->forceDelete();

        $testModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
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
