<?php

use October\Rain\Boilerplate\Base;
use October\Rain\Boilerplate\Templates\Model;

class BaseTest extends TestCase
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
        $result = self::callProtectedMethod($obj, 'processVars', [['name' => 'duffMan']]);

        $this->assertArrayHasKey('name', $result);
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

        $this->assertEquals('duffMan', $result['name']);
        $this->assertEquals('DUFFMAN', $result['upper_name']);
        $this->assertEquals('DUFFMEN', $result['upper_plural_name']);
        $this->assertEquals('DUFFMAN', $result['upper_singular_name']);
        $this->assertEquals('duffman', $result['lower_name']);
        $this->assertEquals('duffmen', $result['lower_plural_name']);
        $this->assertEquals('duffman', $result['lower_singular_name']);
        $this->assertEquals('duff_man', $result['snake_name']);
        $this->assertEquals('duffmen', $result['snake_plural_name']);
        $this->assertEquals('duff_man', $result['snake_singular_name']);
        $this->assertEquals('DuffMan', $result['studly_name']);
        $this->assertEquals('Duffmen', $result['studly_plural_name']);
        $this->assertEquals('DuffMan', $result['studly_singular_name']);
        $this->assertEquals('duffMan', $result['camel_name']);
        $this->assertEquals('duffmen', $result['camel_plural_name']);
        $this->assertEquals('duffMan', $result['camel_singular_name']);
        $this->assertEquals('duffmen', $result['plural_name']);
        $this->assertEquals('duffMan', $result['singular_name']);
    }
}