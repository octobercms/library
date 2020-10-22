<?php

use October\Rain\Database\Model;
use Illuminate\Database\Schema\Blueprint;
use October\Rain\Support\Facades\Schema;

class ModelTest extends TestCase
{
    public function testAddCasts()
    {
        $model = new TestModel();

        $this->assertEquals(['id' => 'int'], $model->getCasts());

        $model->addCasts(['foo' => 'int']);

        $this->assertEquals(['id' => 'int', 'foo' => 'int'], $model->getCasts());
    }

    public function testFalse()
    {
        $model = new TestModel();

        // Test base guarded property
        $this->assertTrue($model->isGuarded('data'));

        // Test variations on casing
        $this->assertTrue($model->isGuarded('DATA'));

        // Test JSON columns
        $this->assertTrue($model->isGuarded('data->key'));
    }
}

class TestModel extends Model
{
    protected $guarded = ['id', 'ID', 'data'];

    public $table = 'test_model';
}
