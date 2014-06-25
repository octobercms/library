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
 * Example class that has extensions enabled
 */
class ExampleExtendableClass extends Extendable
{
    public $implement = ['ExampleBehaviorClass1'];

    public $classAttribute;
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
}