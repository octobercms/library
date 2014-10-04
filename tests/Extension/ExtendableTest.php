<?php

use October\Rain\Extension\Extendable;
use October\Rain\Extension\ExtensionBase;

/**
 * Example behavior classes
 */
class ExampleBehaviorClass1 extends ExtensionBase
{
    public $behaviorAttribute;

    public function getFoo()
    {
        return 'foo';
    }

    public static function getStaticBar()
    {
        return 'bar';
    }

    public static function vanillaIceIce()
    {
        return 'cream';
    }
}

class ExampleBehaviorClass2 extends ExtensionBase
{
    public $behaviorAttribute;

    public function getFoo()
    {
        return 'bar';
    }
}

/*
 * Example class that has an invalid implementation
 */
class InvalidExtendableClass extends Extendable
{
    public $implement = 24;

    public $classAttribute;
}

/*
 * Example class that has extensions enabled
 */
class ExampleExtendableClass extends Extendable
{
    public $implement = ['ExampleBehaviorClass1'];

    public $classAttribute;

    public static function vanillaIceIce()
    {
        return 'baby';
    }
}

/**
 * A normal class without extensions enabled
 */
class ExampleClass
{
    public static function getName()
    {
        return 'october';
    }
}

class ExtendableTest extends TestCase
{

    public function testSettingDeclaredAttributeOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->classAttribute = 'Test';
        $this->assertEquals('Test', $subject->classAttribute);
    }

    public function testSettingUndeclaredAttributeOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->newAttribute = 'Test';
        $this->assertEquals('Test', $subject->newAttribute);
    }

    public function testSettingDeclaredAttributeOnBehavior()
    {
        $subject = new ExampleExtendableClass;
        $behavior = $subject->getClassExtension('ExampleBehaviorClass1');

        $subject->behaviorAttribute = 'Test';
        $this->assertEquals('Test', $subject->behaviorAttribute);
        $this->assertEquals('Test', $behavior->behaviorAttribute);
        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass1'));
    }

    public function testDynamicallyExtendingClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->extendClassWith('ExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass2'));
    }

    public function testDynamicMethodOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExampleBehaviorClass1');

        $this->assertEquals('foo', $subject->getFoo());
        $this->assertEquals('foo', $subject->getFooAnotherWay());
    }

    public function testDynamicExtendAndMethodOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->extendClassWith('ExampleBehaviorClass2');
        $subject->addDynamicMethod('getOriginalFoo', 'getFoo', 'ExampleBehaviorClass1');

        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass2'));
        $this->assertEquals('bar', $subject->getFoo());
        $this->assertEquals('foo', $subject->getOriginalFoo());
    }

    public function testDynamicClosureOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->addDynamicMethod('sayHello', function() {
            return 'Hello world';
        });

        $this->assertEquals('Hello world', $subject->sayHello());
    }

    public function testDynamicCallableOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->addDynamicMethod('getAppName', ['ExampleClass', 'getName']);

        $this->assertEquals('october', $subject->getAppName());
    }

    public function testCallingStaticMethod()
    {
        $result = ExampleExtendableClass::getStaticBar();
        $this->assertEquals('bar', $result);

        $result = ExampleExtendableClass::vanillaIceIce();
        $this->assertEquals('baby', $result);
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Class ExampleExtendableClass does not have a method definition for undefinedMethod
     */
    public function testCallingUndefinedStaticMethod()
    {
        $result = ExampleExtendableClass::undefinedMethod();
        $this->assertEquals('bar', $result);
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Class InvalidExtendableClass contains an invalid $implement value
     */
    public function testInvalidImplementValue()
    {
        $result = new InvalidExtendableClass;
    }
}