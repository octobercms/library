<?php

use October\Rain\Html\Helper as HtmlHelper;

class HtmlHelperTest extends TestCase
{
    public function testNameToId()
    {
        $result = HtmlHelper::nameToId('field');
        $this->assertEquals('field', $result);

        $result = HtmlHelper::nameToId('field[key1]');
        $this->assertEquals('field-key1', $result);

        $result = HtmlHelper::nameToId('field[][key1]');
        $this->assertEquals('field--key1', $result);

        $result = HtmlHelper::nameToId('field[key1][key2][key3]');
        $this->assertEquals('field-key1-key2-key3', $result);
    }

    public function testNameToArray()
    {
        $result = HtmlHelper::nameToArray('field');
        $this->assertIsArray($result);
        $this->assertEquals(1, count($result));
        $this->assertTrue(in_array('field', $result));

        $result = HtmlHelper::nameToArray('field[key1]');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[][key1]');
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[key1][key2][key3]');
        $this->assertIsArray($result);
        $this->assertEquals(4, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));
        $this->assertTrue(in_array('key2', $result));
        $this->assertTrue(in_array('key3', $result));
    }
}
