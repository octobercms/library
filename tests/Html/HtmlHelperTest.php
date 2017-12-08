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
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertContains('field', $result);

        $result = HtmlHelper::nameToArray('field[key1]');
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $this->assertContains('field', $result);
        $this->assertContains('key1', $result);

        $result = HtmlHelper::nameToArray('field[][key1]');
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $this->assertContains('field', $result);
        $this->assertContains('key1', $result);

        $result = HtmlHelper::nameToArray('field[key1][key2][key3]');
        $this->assertInternalType('array', $result);
        $this->assertCount(4, $result);
        $this->assertContains('field', $result);
        $this->assertContains('key1', $result);
        $this->assertContains('key2', $result);
        $this->assertContains('key3', $result);
    }
}
