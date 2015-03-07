<?php

use October\Rain\Router\Helper;

class RouterHelperTest extends TestCase
{
    public function testSegmentIsOptional()
    {
        $value = Helper::segmentIsOptional(':my_param_name');
        $this->assertFalse($value);

        $value = Helper::segmentIsOptional(':my_param_name?');
        $this->assertTrue($value);

        $value = Helper::segmentIsOptional(':my_param_name?default value');
        $this->assertTrue($value);

        $value = Helper::segmentIsOptional(':my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$');
        $this->assertFalse($value);

        $value = Helper::segmentIsOptional(':my_param_name?default value|^[a-z]+[0-9]?$');
        $this->assertTrue($value);
    }

    public function testParameterNameMethod()
    {
        $value = Helper::getParameterName(':my_param_name');
        $this->assertEquals('my_param_name', $value);

        $value = Helper::getParameterName(':my_param_name?');
        $this->assertEquals('my_param_name', $value);

        $value = Helper::getParameterName(':my_param_name?default value');
        $this->assertEquals('my_param_name', $value);

        $value = Helper::getParameterName(':my_param_name|^[a-z]+[0-9]?$');
        $this->assertEquals('my_param_name', $value);

        $value = Helper::getParameterName(':my_param_name|^[a-z]+[0-9]?$');
        $this->assertEquals('my_param_name', $value);

        $value = Helper::getParameterName(':my_param_name?default value|^[a-z]+[0-9]?$');
        $this->assertEquals('my_param_name', $value);
    }

    public function testSegmentRegexp()
    {
        $value = Helper::getSegmentRegExp(':my_param_name');
        $this->assertFalse($value);

        $value = Helper::getSegmentRegExp(':my_param_name?');
        $this->assertFalse($value);

        $value = Helper::getSegmentRegExp(':my_param_name?default value');
        $this->assertFalse($value);

        $value = Helper::getSegmentRegExp(':my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$');
        $this->assertEquals('/^[a-z]+[0-9]?$|^[a-z]{3}$/', $value);

        $value = Helper::getSegmentRegExp(':my_param_name?default value|^[a-z]+[0-9]?$');
        $this->assertEquals('/^[a-z]+[0-9]?$/', $value);
    }

    public function testDefaultValue()
    {
        $value = Helper::getSegmentDefaultValue(':my_param_name');
        $this->assertFalse($value);

        $value = Helper::getSegmentDefaultValue(':my_param_name?');
        $this->assertFalse($value);

        $value = Helper::getSegmentDefaultValue(':my_param_name?default value');
        $this->assertEquals('default value', $value);

        $value = Helper::getSegmentDefaultValue(':my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$');
        $this->assertFalse($value);

        $value = Helper::getSegmentDefaultValue(':my_param_name?default value|^[a-z]+[0-9]?$');
        $this->assertEquals('default value', $value);
    }
}