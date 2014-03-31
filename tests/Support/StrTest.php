<?php

use October\Rain\Support\Str;

class StrTest extends TestCase
{

    public function testEvalBoolean()
    {
        $result = Str::evalBoolean('yes');
        $this->assertTrue($result);

        $result = Str::evalBoolean('y');
        $this->assertTrue($result);

        $result = Str::evalBoolean('true');
        $this->assertTrue($result);

        $result = Str::evalBoolean('no');
        $this->assertFalse($result);

        $result = Str::evalBoolean('n');
        $this->assertFalse($result);

        $result = Str::evalBoolean('false');
        $this->assertFalse($result);

        $result = Str::evalBoolean('nothing to see here');
        $this->assertEquals('nothing to see here', $result);
    }

    public function testEvalHtmlArray()
    {
        $result = Str::evalHtmlArray('field');
        $this->assertInternalType('array', $result);
        $this->assertEquals(1, count($result));
        $this->assertTrue(in_array('field', $result));

        $result = Str::evalHtmlArray('field[key1]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = Str::evalHtmlArray('field[][key1]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));

        $result = Str::evalHtmlArray('field[key1][key2][key3]');
        $this->assertInternalType('array', $result);
        $this->assertEquals(4, count($result));
        $this->assertTrue(in_array('field', $result));
        $this->assertTrue(in_array('key1', $result));
        $this->assertTrue(in_array('key2', $result));
        $this->assertTrue(in_array('key3', $result));
    }

}