<?php

use October\Rain\Router\Helper;

/**
 * RouterHelperTest
 */
class RouterHelperTest extends TestCase
{
    /**
     * testvalidateUrl
     */
    public function testvalidateUrl()
    {
        $validUrls = [
            '/october/cms',
            '/october/:cms',
            '/october/:cms?',
            '/october/:cms?defaultvalue',
            '/october/:cms|^[a-z]+[0-9]?$|^[a-z]{3}$',
            '/october//cms',
        ];

        $invalidUrls = [
            'october/cms',
            '/october/cms#',
            '/october/cms!',
            '/october/cms?page=1',
            '/october/c m s',
            '/october/:cms?default value',
        ];

        foreach ($validUrls as $url) {
            $this->assertTrue(Helper::validateUrl($url));
        }

        foreach ($invalidUrls as $url) {
            $this->assertFalse(Helper::validateUrl($url));
        }
    }

    /**
     * testSegmentize
     */
    public function testSegmentize()
    {
        // Single param
        $value = Helper::segmentizeUrl("/:my_param_name");
        $this->assertEquals([':my_param_name'], $value);

        // Single param with regex
        $value = Helper::segmentizeUrl('/:my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$');
        $this->assertEquals([':my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$'], $value);

        // Multiple params
        $value = Helper::segmentizeUrl("/param1/:my_param_name");
        $this->assertEquals(['param1', ':my_param_name'], $value);

        // Skip empty params
        $value = Helper::segmentizeUrl("/param1//:my_param_name");
        $this->assertEquals(['param1', ':my_param_name'], $value);

        // Multiple params with regex
        $value = Helper::segmentizeUrl('/:my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$/param2');
        $this->assertEquals([':my_param_name|^[a-z]+[0-9]?$|^[a-z]{3}$', 'param2'], $value);

        // // Escaped regex
        // $value = Helper::segmentizeUrl('/:my_param_name*|^[a-z]+\/\d+$');
        // $this->assertEquals([':my_param_name*|^[a-z]+\/\d+$'], $value);

        // // Escaped regex with multiple params
        // $value = Helper::segmentizeUrl('/:my_param_name*|^[a-z]+\/\d+$/param2');
        // $this->assertEquals([':my_param_name*|^[a-z]+\/\d+$', 'param2'], $value);
    }

    /**
     * testSegmentIsWildcard
     */
    public function testSegmentIsWildcard()
    {
        // Non wildcard no regex
        $value = Helper::segmentIsWildcard(":my_param_name");
        $this->assertFalse($value);

        // Wildcard no regex
        $value = Helper::segmentIsWildcard(":my_param_name*");
        $this->assertTrue($value);

        // // Non wildcard with regex
        // $value = Helper::segmentIsWildcard(":my_param_name|^[a-z]+[0-9]?$");
        // $this->assertFalse($value);

        // // Wildcard with regex
        // $value = Helper::segmentIsWildcard(":my_param_name*|^[a-z]+[0-9]?$");
        // $this->assertTrue($value);

        // // Non Wildcard with regex ending in *
        // $value = Helper::segmentIsWildcard(":my_param_name|^[a-z]+[0-9]*");
        // $this->assertFalse($value);
    }

    /**
     * testSegmentIsOptional
     */
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

    /**
     * testParameterNameMethod
     */
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

    /**
     * testSegmentRegexp
     */
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

    /**
     * testDefaultValue
     */
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

    /**
     * testReplaceParameters
     */
    public function testReplaceParameters()
    {
        $value = Helper::replaceParameters(['param1'=>'dynamic1'], 'static1/:param1/static2');
        $this->assertEquals('static1/dynamic1/static2', $value);

        $value = Helper::replaceParameters(['param1'=>'dynamic1'], 'static1/:param1/:param2');
        $this->assertEquals('static1/dynamic1/:param2', $value);

        $value = Helper::replaceParameters(['param1'=>'dynamic1', 'param2'=>'dynamic2'], 'static1/:param1/:param2');
        $this->assertEquals('static1/dynamic1/dynamic2', $value);

        $value = Helper::replaceParameters(['longer_param'=>'replacement'], 'Non-URL string: contains :longer_param, :other_param, and non-param colon');
        $this->assertEquals('Non-URL string: contains replacement, :other_param, and non-param colon', $value);
    }
}
