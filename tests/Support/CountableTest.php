<?php
class CountableTest extends TestCase
{
    public function testCountable()
    {
        $array = [
            'foo' => 'bar',
            'foo2' => 'bar2'
        ];

        $this->assertTrue(is_countable($array));

        $collection = collect([
            'foo' => 'bar',
            'foo2' => 'bar2'
        ]);

        $this->assertTrue(is_countable($collection));

        $arrayObj = new ArrayObject([
            'foo' => 'bar',
            'foo2' => 'bar2'
        ]);

        $this->assertTrue(is_countable($arrayObj));

        $string = 'Test string';

        $this->assertFalse(is_countable($string));

        $int = 5;

        $this->assertFalse(is_countable($int));

        $emptyArray = [];

        $this->assertTrue(is_countable($emptyArray));
    }
}
