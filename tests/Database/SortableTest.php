<?php

class SortableTest extends DbTestCase
{
    public function testOrderByIsAutomaticallyAdded()
    {
        $model = new TestSortableModel();
        $query = $model->newQuery()->toSql();

        $this->assertEquals('select * from "test" order by "sort_order" asc', $query);
    }

    public function testOrderByCanBeOverridden()
    {
        $model = new TestSortableModel();
        $query1 = $model->newQuery()->orderBy('name')->orderBy('email', 'desc')->toSql();
        $query2 = $model->newQuery()->orderBy('sort_order')->orderBy('name')->toSql();

        $this->assertEquals('select * from "test" order by "name" asc, "email" desc', $query1);
        $this->assertEquals('select * from "test" order by "sort_order" asc, "name" asc', $query2);
    }
}

class TestSortableModel extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Sortable;

    protected $table = 'test';
}
