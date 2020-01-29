<?php

class ModelAddersTest extends \October\Rain\Tests\TestCase
{
    public function testAddCasts()
    {
        $model = new TestModel();

        $this->assertEquals(['id' => 'int'], $model->getCasts());

        $model->addCasts(['foo' => 'int']);

        $this->assertEquals(['id' => 'int', 'foo' => 'int'], $model->getCasts());
    }
}
