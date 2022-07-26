<?php

class SortableTest extends TestCase
{
    public function setUp(): void
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
        $model = new TestModel;
        $query = $model->newQuery()->toSql();

        $this->assertEquals('select * from "test" order by "test"."sort_order" asc', $query);
    }

    public function testOrderByCanBeOverridden()
    {
        $model = new TestModel;
        $query1 = $model->newQuery()->orderBy('name')->orderBy('email', 'desc')->toSql();
        $query2 = $model->newQuery()->orderBy('sort_order')->orderBy('name')->toSql();

        $this->assertEquals('select * from "test" order by "name" asc, "email" desc', $query1);
        $this->assertEquals('select * from "test" order by "sort_order" asc, "name" asc', $query2);
    }

    public function testSkipsOrderByWhenCountingTotalNumberOfModels()
    {
        // Given a ParentTestModel which has many TestModels, which are sortable
        $parentModel = new ParentTestModel;

        // When we try to get the total number of related TestModels (i.e. when using a listcolumn with useRelationCount)
        $query = $parentModel->newQuery()->withCount('testmodels')->toSql();

        // Then expect the Sortable trait to skip adding an unnecessary ORDER BY to avoid SQL errors
        $this->assertEquals('select "test_parent".*, (select count(*) from "test" where "test_parent"."id" = "test"."parent_test_model_id") as "testmodels_count" from "test_parent"', $query);
    }
}

class TestModel extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sortable;

    protected $table = 'test';
}

class ParentTestModel extends \October\Rain\Database\Model
{
    protected $table = 'test_parent';

    public $hasMany = [
        'testmodels' => TestModel::class
    ];

}
