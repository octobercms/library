<?php

class SortableTest extends TestCase
{
    public function setUp()
    {
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public function testOrderByIsAutomaticallyAdded()
    {
        $model = new TestModel();
        $query = $model->newQuery()->toSql();

        $this->assertEquals('select * from "test" order by "sort_order" asc', $query);
    }

    public function testOrderByCanBeOverridden()
    {
        $model = new TestModel();
        $query1 = $model->newQuery()->orderBy('name')->orderBy('email', 'desc')->toSql();
        $query2 = $model->newQuery()->orderBy('sort_order')->orderBy('name')->toSql();

        $this->assertEquals('select * from "test" order by "name" asc, "email" desc', $query1);
        $this->assertEquals('select * from "test" order by "sort_order" asc, "name" asc', $query2);
    }
}

class TestModel extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sortable;

    protected $table = 'test';
}
