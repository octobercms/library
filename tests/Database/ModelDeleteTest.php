<?php

use Illuminate\Database\Capsule\Manager;
use October\Rain\Database\Model;

/**
 * ModelDeleteTest
 */
class ModelDeleteTest extends TestCase
{
    /**
     * setUp test
     */
    public function setUp(): void
    {
        $capsule = new Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('test_delete_entries', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });
    }

    /**
     * testDeletedModelNoLongerExists confirms a deleted model is flagged as not existing
     */
    public function testDeletedModelNoLongerExists()
    {
        $model = TestDeleteEntry::create(['name' => 'First']);
        $this->assertTrue($model->exists);

        $model->delete();

        $this->assertFalse($model->exists);
        $this->assertSame(0, TestDeleteEntry::count());
    }

    /**
     * testDeletedModelIsInsertedWhenSavedAgain confirms saving after a delete creates the record again
     */
    public function testDeletedModelIsInsertedWhenSavedAgain()
    {
        $model = TestDeleteEntry::create(['name' => 'First']);
        $model->delete();

        $model->save();

        $this->assertSame(1, TestDeleteEntry::count());
    }
}

/**
 * TestDeleteEntry example class
 */
class TestDeleteEntry extends Model
{
    protected $table = 'test_delete_entries';

    protected $fillable = ['name'];

    public $timestamps = false;
}
