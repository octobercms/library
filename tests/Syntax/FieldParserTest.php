<?php

use October\Rain\Syntax\FieldParser;

class FieldParserTest extends TestCase
{

    public function testParse()
    {
        $content = '';
        $content .= '{text name="field1" label="Field 1"}{/text}'.PHP_EOL;
        $content .= '{textarea name="field1" label="Field 1 Again"}{/textarea}'.PHP_EOL;
        $content .= '{text name="field2" label="Field 2"}Default Text{/text}'.PHP_EOL;
        $content .= '{textarea name="field3" label="Field 3"}Default Text{/textarea}'.PHP_EOL;
        $content .= '{textarea name="field4" label="Field 4"}Invalid Tag{/invalid}'.PHP_EOL;

        $result = FieldParser::parse($content);
        $tags = $result->getTags();
        $fields = $result->getFields();

        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
        $this->assertArrayHasKey('field3', $fields);
        $this->assertArrayNotHasKey('field4', $fields);

        $this->assertArrayHasKey('field1', $tags);
        $this->assertArrayHasKey('field2', $tags);
        $this->assertArrayHasKey('field3', $tags);
        $this->assertArrayNotHasKey('field4', $tags);

        $this->assertEquals('{textarea name="field1" label="Field 1 Again"}{/textarea}', $tags['field1']);

        $this->assertArrayNotHasKey('name', $fields['field1']);
        $this->assertArrayNotHasKey('name', $fields['field2']);
        $this->assertArrayNotHasKey('name', $fields['field3']);

        $this->assertArrayHasKey('type', $fields['field1']);
        $this->assertArrayHasKey('type', $fields['field2']);
        $this->assertArrayHasKey('type', $fields['field3']);

        $this->assertEquals('textarea', $fields['field1']['type']);
    }

    public function testProcessTag()
    {
        $parser = new FieldParser;
        $content = '';
        $content .= '{text name="websiteName" label="Website Name" size="large"}{/text}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name" color="re\"d"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name" shape="circle"}{/text}';
        $content .= '{text label="Unnamed" distance="400m"}Foobar{/text}';
        $content .= '{foobar name="nullName" label="Valid tag, not searched by this test"}{/foobar}';
        list($tags, $fields) = self::callProtectedMethod($parser, 'processTags', [$content]);

        $unnamedTag = md5('{text label="Unnamed" distance="400m"}Foobar{/text}');

        $this->assertArrayNotHasKey('Unnamed', $fields);
        $this->assertArrayNotHasKey('nullName', $fields);
        $this->assertArrayHasKey('websiteName', $fields);
        $this->assertArrayHasKey('blogName', $fields);
        $this->assertArrayHasKey('storeName', $fields);
        $this->assertArrayHasKey($unnamedTag, $fields);

        $this->assertArrayNotHasKey('name', $fields['websiteName']);
        $this->assertArrayHasKey('label', $fields['websiteName']);
        $this->assertArrayHasKey('size', $fields['websiteName']);
        $this->assertArrayHasKey('type', $fields['websiteName']);
        $this->assertArrayHasKey('default', $fields['websiteName']);
        $this->assertEquals('Website Name', $fields['websiteName']['label']);
        $this->assertEquals('large', $fields['websiteName']['size']);
        $this->assertEquals('text', $fields['websiteName']['type']);
        $this->assertNotNull($fields['websiteName']['default']);
        $this->assertEquals('', $fields['websiteName']['default']);

        $this->assertArrayNotHasKey('name', $fields['blogName']);
        $this->assertArrayHasKey('label', $fields['blogName']);
        $this->assertArrayHasKey('color', $fields['blogName']);
        $this->assertArrayHasKey('type', $fields['blogName']);
        $this->assertArrayHasKey('default', $fields['blogName']);
        $this->assertEquals('Blog Name', $fields['blogName']['label']);
        $this->assertEquals('re\"d', $fields['blogName']['color']);
        $this->assertEquals('text', $fields['blogName']['type']);
        $this->assertNotNull($fields['blogName']['default']);
        $this->assertEquals('OctoberCMS', $fields['blogName']['default']);

        $this->assertArrayNotHasKey('name', $fields['storeName']);
        $this->assertArrayHasKey('label', $fields['storeName']);
        $this->assertArrayHasKey('shape', $fields['storeName']);
        $this->assertArrayHasKey('type', $fields['storeName']);
        $this->assertArrayHasKey('default', $fields['storeName']);
        $this->assertEquals('Store Name', $fields['storeName']['label']);
        $this->assertEquals('circle', $fields['storeName']['shape']);
        $this->assertEquals('text', $fields['storeName']['type']);
        $this->assertNotNull($fields['storeName']['default']);
        $this->assertEquals('', $fields['storeName']['default']);


        $this->assertArrayNotHasKey('name', $fields[$unnamedTag]);
        $this->assertArrayHasKey('label', $fields[$unnamedTag]);
        $this->assertArrayHasKey('distance', $fields[$unnamedTag]);
        $this->assertArrayHasKey('type', $fields[$unnamedTag]);
        $this->assertArrayHasKey('default', $fields[$unnamedTag]);
        $this->assertEquals('Unnamed', $fields[$unnamedTag]['label']);
        $this->assertEquals('400m', $fields[$unnamedTag]['distance']);
        $this->assertEquals('text', $fields[$unnamedTag]['type']);
        $this->assertNotNull($fields[$unnamedTag]['default']);
        $this->assertEquals('Foobar', $fields[$unnamedTag]['default']);

        $this->assertArrayNotHasKey('Unnamed', $tags);
        $this->assertArrayNotHasKey('nullName', $tags);
        $this->assertArrayHasKey('websiteName', $tags);
        $this->assertArrayHasKey('blogName', $tags);
        $this->assertArrayHasKey('storeName', $tags);
        $this->assertArrayHasKey($unnamedTag, $tags);
    }

    public function testProcessTagsRegex()
    {
        $parser = new FieldParser;
        $content = '';
        $content .= '{text name="websiteName" label="Website Name"}{/text}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name"}{/text}';
        $result = self::callProtectedMethod($parser, 'processTagsRegex', [$content, ['text']]);

        $this->assertArrayHasKey(0, $result[2]);
        $this->assertArrayHasKey(1, $result[2]);
        $this->assertArrayHasKey(2, $result[2]);

        $this->assertEquals('name="websiteName" label="Website Name"}', $result[2][0]);
        $this->assertEquals('name="blogName" label="Blog Name"}OctoberCMS', $result[2][1]);
        $this->assertEquals('name="storeName" label="Store Name"}', $result[2][2]);
    }

    public function testProcessParamsRegex()
    {
        $parser = new FieldParser;
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