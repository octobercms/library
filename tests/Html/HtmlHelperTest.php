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
        $this->assertEquals(1, count($result));
        $this->assertTrue(in_array('field', $result));

        $result = HtmlHelper::nameToArray('field[key1]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[][key1]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = HtmlHelper::nameToArray('field[key1][key2][key3]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(4, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));
        $this->assertTrue(in_array('key2', $result));
        $this->assertTrue(in_array('key3', $result));
    }

    public function testStrip()
    {
        $result = HtmlHelper::strip('<p>hello</p>');
        $this->assertEquals('hello', $result);
    }

    public function testLimit()
    {
        $result = HtmlHelper::limit('<p>The quick brown fox jumped over the lazy dog</p>', 10);
        $this->assertEquals('<p>The quick ...</p>', $result);

        $result = HtmlHelper::limit('<p>The quick brown fox jumped over the lazy dog</p>', 20, '!!!');
        $this->assertEquals('<p>The quick brown fox !!!</p>', $result);
    }

    public function testClean()
    {
        $result = HtmlHelper::clean('<script>window.location = "http://google.com"</script>');
        $this->assertEquals('window.location = "http://google.com"', $result);

        $result = HtmlHelper::clean('<span style="width: expression(alert(\'Ping!\'));"></span>');
        $this->assertEquals('<span ></span>', $result);
    }

}