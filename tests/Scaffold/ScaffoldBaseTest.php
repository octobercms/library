<?php

use October\Rain\Scaffold\Base;
use October\Rain\Scaffold\Templates\Model;

class ScaffoldBaseTest extends TestCase
{
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

    //
    // Tests
    //

    public function testProcessVars()
    {
        $obj = new Model();
        $testVars = [
            'name' => 'duffMan',
            'author' => 'Moe',
            'plugin' => 'Duff',
            'class' => 'TaxClass',
        ];

        $result = self::callProtectedMethod($obj, 'processVars', [$testVars]);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('author', $result);
        $this->assertArrayHasKey('plugin', $result);
        $this->assertArrayHasKey('title_name', $result);
        $this->assertArrayHasKey('title_author', $result);
        $this->assertArrayHasKey('title_plugin', $result);
        $this->assertArrayHasKey('upper_name', $result);
        $this->assertArrayHasKey('upper_plural_name', $result);
        $this->assertArrayHasKey('upper_singular_name', $result);
        $this->assertArrayHasKey('lower_name', $result);
        $this->assertArrayHasKey('lower_plural_name', $result);
        $this->assertArrayHasKey('lower_singular_name', $result);
        $this->assertArrayHasKey('snake_name', $result);
        $this->assertArrayHasKey('snake_plural_name', $result);
        $this->assertArrayHasKey('snake_singular_name', $result);
        $this->assertArrayHasKey('studly_name', $result);
        $this->assertArrayHasKey('studly_plural_name', $result);
        $this->assertArrayHasKey('studly_singular_name', $result);
        $this->assertArrayHasKey('camel_name', $result);
        $this->assertArrayHasKey('camel_plural_name', $result);
        $this->assertArrayHasKey('camel_singular_name', $result);
        $this->assertArrayHasKey('plural_name', $result);
        $this->assertArrayHasKey('singular_name', $result);
        $this->assertArrayHasKey('upper_author', $result);
        $this->assertArrayHasKey('upper_plural_author', $result);
        $this->assertArrayHasKey('upper_singular_author', $result);
        $this->assertArrayHasKey('lower_author', $result);
        $this->assertArrayHasKey('lower_plural_author', $result);
        $this->assertArrayHasKey('lower_singular_author', $result);
        $this->assertArrayHasKey('snake_author', $result);
        $this->assertArrayHasKey('snake_plural_author', $result);
        $this->assertArrayHasKey('snake_singular_author', $result);
        $this->assertArrayHasKey('studly_author', $result);
        $this->assertArrayHasKey('studly_plural_author', $result);
        $this->assertArrayHasKey('studly_singular_author', $result);
        $this->assertArrayHasKey('camel_author', $result);
        $this->assertArrayHasKey('camel_plural_author', $result);
        $this->assertArrayHasKey('camel_singular_author', $result);
        $this->assertArrayHasKey('plural_author', $result);
        $this->assertArrayHasKey('singular_author', $result);
        $this->assertArrayHasKey('upper_plugin', $result);
        $this->assertArrayHasKey('upper_plural_plugin', $result);
        $this->assertArrayHasKey('upper_singular_plugin', $result);
        $this->assertArrayHasKey('lower_plugin', $result);
        $this->assertArrayHasKey('lower_plural_plugin', $result);
        $this->assertArrayHasKey('lower_singular_plugin', $result);
        $this->assertArrayHasKey('snake_plugin', $result);
        $this->assertArrayHasKey('snake_plural_plugin', $result);
        $this->assertArrayHasKey('snake_singular_plugin', $result);
        $this->assertArrayHasKey('studly_plugin', $result);
        $this->assertArrayHasKey('studly_plural_plugin', $result);
        $this->assertArrayHasKey('studly_singular_plugin', $result);
        $this->assertArrayHasKey('camel_plugin', $result);
        $this->assertArrayHasKey('camel_plural_plugin', $result);
        $this->assertArrayHasKey('camel_singular_plugin', $result);
        $this->assertArrayHasKey('plural_plugin', $result);
        $this->assertArrayHasKey('singular_plugin', $result);

        $this->assertEquals('duffMan', $result['name']);
        $this->assertEquals('Moe', $result['author']);
        $this->assertEquals('Duff', $result['plugin']);
        $this->assertEquals('Duff Man', $result['title_name']);
        $this->assertEquals('DUFFMAN', $result['upper_name']);
        $this->assertEquals('DUFFMEN', $result['upper_plural_name']);
        $this->assertEquals('DUFFMAN', $result['upper_singular_name']);
        $this->assertEquals('duffman', $result['lower_name']);
        $this->assertEquals('duffmen', $result['lower_plural_name']);
        $this->assertEquals('duffman', $result['lower_singular_name']);
        $this->assertEquals('duff_man', $result['snake_name']);
        $this->assertEquals('duff_men', $result['snake_plural_name']);
        $this->assertEquals('duff_man', $result['snake_singular_name']);
        $this->assertEquals('DuffMan', $result['studly_name']);
        $this->assertEquals('DuffMen', $result['studly_plural_name']);
        $this->assertEquals('DuffMan', $result['studly_singular_name']);
        $this->assertEquals('duffMan', $result['camel_name']);
        $this->assertEquals('duffMen', $result['camel_plural_name']);
        $this->assertEquals('duffMan', $result['camel_singular_name']);
        $this->assertEquals('duffMen', $result['plural_name']);
        $this->assertEquals('duffMan', $result['singular_name']);
        $this->assertEquals('MOE', $result['upper_author']);
        $this->assertEquals('MOES', $result['upper_plural_author']);
        $this->assertEquals('MOE', $result['upper_singular_author']);
        $this->assertEquals('moe', $result['lower_author']);
        $this->assertEquals('moes', $result['lower_plural_author']);
        $this->assertEquals('moe', $result['lower_singular_author']);
        $this->assertEquals('moe', $result['snake_author']);
        $this->assertEquals('moes', $result['snake_plural_author']);
        $this->assertEquals('moe', $result['snake_singular_author']);
        $this->assertEquals('Moe', $result['studly_author']);
        $this->assertEquals('Moes', $result['studly_plural_author']);
        $this->assertEquals('Moe', $result['studly_singular_author']);
        $this->assertEquals('moe', $result['camel_author']);
        $this->assertEquals('moes', $result['camel_plural_author']);
        $this->assertEquals('moe', $result['camel_singular_author']);
        $this->assertEquals('Moes', $result['plural_author']);
        $this->assertEquals('Moe', $result['singular_author']);
        $this->assertEquals('DUFF', $result['upper_plugin']);
        $this->assertEquals('DUFFS', $result['upper_plural_plugin']);
        $this->assertEquals('DUFF', $result['upper_singular_plugin']);
        $this->assertEquals('duff', $result['lower_plugin']);
        $this->assertEquals('duffs', $result['lower_plural_plugin']);
        $this->assertEquals('duff', $result['lower_singular_plugin']);
        $this->assertEquals('duff', $result['snake_plugin']);
        $this->assertEquals('duffs', $result['snake_plural_plugin']);
        $this->assertEquals('duff', $result['snake_singular_plugin']);
        $this->assertEquals('Duff', $result['studly_plugin']);
        $this->assertEquals('Duffs', $result['studly_plural_plugin']);
        $this->assertEquals('Duff', $result['studly_singular_plugin']);
        $this->assertEquals('duff', $result['camel_plugin']);
        $this->assertEquals('duffs', $result['camel_plural_plugin']);
        $this->assertEquals('duff', $result['camel_singular_plugin']);
        $this->assertEquals('Duffs', $result['plural_plugin']);
        $this->assertEquals('Duff', $result['singular_plugin']);
        $this->assertEquals('Tax Class', $result['title_class']);
        $this->assertEquals('Tax Class', $result['title_singular_class']);
        $this->assertEquals('Tax Classes', $result['title_plural_class']);
        $this->assertEquals('tax class', $result['lower_title_class']);
    }
}