<?php

use October\Rain\Syntax\FieldParser;

class FieldParserTest extends TestCase
{

    public function testParse()
    {
        $content = '';
        $content .= '{text name="websiteName" label="Website Name"}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name"}{/text}';

        $result = FieldParser::parse($content);

    }

    public function testProcessFieldsRegex()
    {
        $parser = new FieldParser('');
        $content = '';
        $content .= '{text name="websiteName" label="Website Name"}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name"}{/text}';
        $result = self::callProtectedMethod($parser, 'processFieldsRegex', [$content, 'text']);

        $this->assertArrayHasKey(0, $result[1]);
        $this->assertArrayHasKey(1, $result[1]);
        $this->assertArrayHasKey(2, $result[1]);

        $this->assertEquals('name="websiteName" label="Website Name"', $result[1][0]);
        $this->assertEquals('name="blogName" label="Blog Name"', $result[1][1]);
        $this->assertEquals('name="storeName" label="Store Name"', $result[1][2]);

        $this->assertNull($result[2][0]);
        $this->assertEquals('OctoberCMS', $result[2][1]);
        $this->assertEquals('', $result[2][2]);
    }

    public function testProcessParamsRegex()
    {
        $parser = new FieldParser('');
        $content = 'name="test" comment="This is a test"';
        $result = self::callProtectedMethod($parser, 'processParamsRegex', [$content]);

        $this->assertArrayHasKey(0, $result[1]);
        $this->assertArrayHasKey(1, $result[1]);
        $this->assertArrayHasKey(0, $result[2]);
        $this->assertArrayHasKey(1, $result[2]);
        $this->assertEquals('name', $result[1][0]);
        $this->assertEquals('comment', $result[1][1]);
        $this->assertEquals('test', $result[2][0]);
        $this->assertEquals('This is a test', $result[2][1]);

        $content = 'name="te\"st" comment="This\" is a test"';
        $result = self::callProtectedMethod($parser, 'processParamsRegex', [$content]);

        $this->assertArrayHasKey(0, $result[1]);
        $this->assertArrayHasKey(1, $result[1]);
        $this->assertArrayHasKey(0, $result[2]);
        $this->assertArrayHasKey(1, $result[2]);
        $this->assertEquals('name', $result[1][0]);
        $this->assertEquals('comment', $result[1][1]);
        $this->assertEquals('te\"st', $result[2][0]);
        $this->assertEquals('This\" is a test', $result[2][1]);
    }

    //
    // Helpers
    //

    protected static function callProtectedMethod($object, $name, $params = [])
    {
        $className = get_class($object);
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    public static function getProtectedProperty($object, $name)
    {
        $className = get_class($object);
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public static function setProtectedProperty($object, $name, $value)
    {
        $className = get_class($object);
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property->setValue($object, $value);
    }

}