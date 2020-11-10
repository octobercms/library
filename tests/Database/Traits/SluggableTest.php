<?php

class SluggableTest extends DbTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->createTables();
    }

    public function testSlugGeneration()
    {
        /*
        * Basic usage of slug Generator
        */
        $testModel1 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel2 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test-2');

        $testModel3 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel3->slug, 'test-3');
    }

    public function testSlugGenerationSoftDelete()
    {
        /*
        * Basic usage of slug Generator with softDelete
        */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel2->slug, 'test-2');

        $testSoftModel3 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel3->slug, 'test-3');
    }

    public function testSlugGenerationSoftDeleteAllow()
    {
        /*
        * Basic usage of slug Generator with softDelete
        * And allowTrashedSlugs
        */
        $testSoftModelAllow1 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow1->slug, 'test');

        $testSoftModelAllow2 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow2->slug, 'test-2');

        $testSoftModelAllow3 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow3->slug, 'test-3');
    }

    public function testSlugGenerationWithSoftDeletion()
    {
        /*
        * Slug Generation when identical key is softDeleted
        */
        $testSoftModelAllow1 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow1->slug, 'test');

        $testSoftModelAllow1->delete();
        $this->assertNotNull($testSoftModelAllow1->deleted_at);

        $testSoftModelAllow2 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow2->slug, 'test-2');

        /*
         * Fails with unique constraint and allowTrashedSlugs to false (default)
         */
        $testSoftModel1 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        $this->assertEquals($testSoftModel1->slug, 'test');

        $testSoftModel1->delete();
        $this->assertNotNull($testSoftModel1->deleted_at);

        $ok = true;

        try {
            $testSoftModel2 = TestModelSluggableSoftDelete::Create(['name' => 'test']);
        } catch (\Exception $e) {
            $ok = false;
        }
        $this->assertFalse($ok, 'Test should have failed');

        /**
        * Should ignore deleted slugs without error with no unique constraint
        */
        $testSoftModelNoUnique1 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique1->slug, 'test');

        $testSoftModelNoUnique1->delete();
        $this->assertNotNull($testSoftModelNoUnique1->deleted_at);

        $testSoftModelNoUnique2 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique2->slug, 'test');
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

        $testSoftModelAllow1 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow1->slug, 'test');

        $testSoftModelAllow1->forceDelete();

        $testSoftModelAllow2 = TestModelSluggableSoftDeleteAllow::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelAllow2->slug, 'test');

        $testSoftModelNoUnique1 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique1->slug, 'test');

        $testSoftModelNoUnique1->forceDelete();

        $testSoftModelNoUnique2 = TestModelSluggableSoftDeleteNoUnique::Create(['name' => 'test']);
        $this->assertEquals($testSoftModelNoUnique2->slug, 'test');

        $testModel1 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel1->slug, 'test');

        $testModel1->delete();

        $testModel2 = TestModelSluggable::Create(['name' => 'test']);
        $this->assertEquals($testModel2->slug, 'test');
    }


    protected function createTables()
    {
        $this->db->schema()->create('testSoftDelete', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->db->schema()->create('testSoftDeleteNoUnique', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->softDeletes();
            $table->timestamps();
        });

        $this->db->schema()->create('testSoftDeleteAllow', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->db->schema()->create('test', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }
}

/*
* Class with Sluggable and SoftDelete traits
* with allowTrashedSlugs
*/
class TestModelSluggableSoftDeleteAllow extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDeleteAllow';
    protected $allowTrashedSlugs = true;
}

/*
* Class with Sluggable and SoftDelete traits
* with default behavior (allowTrashedSlugs = false)
*/
class TestModelSluggableSoftDelete extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDelete';
}

/*
* Class with Sluggable and SoftDelete traits
* with default behavior (allowTrashedSlugs = false)
*/
class TestModelSluggableSoftDeleteNoUnique extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'testSoftDeleteNoUnique';
}

/*
* Class with only Sluggable trait
*/
class TestModelSluggable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sluggable;

    protected $slugs = ['slug' => 'name'];
    protected $fillable = ['name'];
    protected $table = 'test';
}
