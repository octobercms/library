<?php

use October\Rain\Database\Model;

class PurgeableTraitTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    public function testPurgeable()
    {
        // Mass-assignment
        $model = TestModelPurgeable::create([
            'name' => 'Test',
            'confirmed' => true
        ]);

        $this->assertNull($model->confirmed);

        // Direct assignment
        $model = new TestModelPurgeable();
        $model->name = 'Test';
        $model->confirmed = true;

        $this->assertTrue($model->confirmed);

        $model->save();

        $this->assertNull($model->confirmed);
    }

    protected function createTables()
    {
        $this->db->schema()->create('test_purge', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('data')->nullable();
            $table->timestamps();
        });
    }
}

class TestModelPurgeable extends Model
{
    use \October\Rain\Database\Traits\Purgeable;

    protected $guarded = ['data'];

    protected $purgeable = ['confirmed'];

    protected $table = 'test_purge';
}
