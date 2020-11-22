<?php

use October\Rain\Database\Model;

class ModelTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTable();
    }

    public function testAddCasts()
    {
        $model = new TestModelGuarded();

        $this->assertEquals(['id' => 'int'], $model->getCasts());

        $model->addCasts(['foo' => 'int']);

        $this->assertEquals(['id' => 'int', 'foo' => 'int'], $model->getCasts());
    }

    public function testIsGuarded()
    {
        $model = new TestModelGuarded();

        // Test base guarded property
        $this->assertTrue($model->isGuarded('data'));

        // Test variations on casing
        $this->assertTrue($model->isGuarded('DATA'));
        $this->assertTrue($model->isGuarded('name'));

        // Test JSON columns
        $this->assertTrue($model->isGuarded('data->key'));
    }

    public function testMassAssignmentOnFieldsNotInDatabase()
    {
        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'is_guarded' => true
        ]);

        $this->assertTrue($model->on_guard); // Guarded property, set by "is_guarded"
        $this->assertNull($model->name); // Guarded property
        $this->assertNull($model->is_guarded); // Non-guarded, non-existent property

        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'is_guarded' => false
        ]);

        $this->assertFalse($model->on_guard);
        $this->assertNull($model->name);
        $this->assertNull($model->is_guarded);

        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data'
        ]);

        $this->assertNull($model->on_guard);
        $this->assertNull($model->name);

        // Check that we cannot mass-fill the "on_guard" property
        $model = TestModelGuarded::create([
            'name' => 'Guard Test',
            'data' => 'Test data',
            'on_guard' => true
        ]);

        $this->assertNull($model->on_guard);
        $this->assertNull($model->name);
    }

    protected function createTable()
    {
        $this->db->schema()->create('test_model', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->boolean('on_guard')->nullable();
            $table->timestamps();
        });
    }
}

class TestModelGuarded extends Model
{
    protected $guarded = ['id', 'ID', 'NAME', 'data', 'on_guard'];

    public $table = 'test_model';

    public function beforeSave()
    {
        if (!is_null($this->is_guarded)) {
            if ($this->is_guarded === true) {
                $this->on_guard = true;
            } elseif ($this->is_guarded === false) {
                $this->on_guard = false;
            }

            unset($this->is_guarded);
        }
    }
}
