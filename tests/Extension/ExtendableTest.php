<?php

use October\Rain\Extension\Extendable;
use October\Rain\Extension\ExtensionBase;

class ExtendableTest extends TestCase
{

    public function testSettingDeclaredPropertyOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->classAttribute = 'Test';
        $this->assertEquals('Test', $subject->classAttribute);
    }

    public function testSettingUndeclaredPropertyOnClass()
    {
        $subject = new ExampleExtendableClass;
        $subject->newAttribute = 'Test';
        $this->assertNull($subject->newAttribute);
        $this->assertFalse(property_exists($subject, 'newAttribute'));
    }

    public function testSettingDeclaredPropertyOnBehavior()
    {
        $subject = new ExampleExtendableClass;
        $behavior = $subject->getClassExtension('ExampleBehaviorClass1');

        $subject->behaviorAttribute = 'Test';
        $this->assertEquals('Test', $subject->behaviorAttribute);
        $this->assertEquals('Test', $behavior->behaviorAttribute);
        $this->assertTrue($subject->isClassExtendedWith('ExampleBehaviorClass1'));
    }

    public function testDynamicPropertyOnClass()
    {
        $subject = new ExampleExtendableClass;
        $this->assertFalse(property_exists($subject, 'newAttribute'));
        $subject->addDynamicProperty('dynamicAttribute', 'Test');
        $this->assertEquals('Test', $subject->dynamicAttribute);
        $this->assertTrue(property_exists($subject, 'dynamicAttribute'));
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
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExampleExtendableClass::undefinedMethod()
     */
    public function testCallingUndefinedStaticMethod()
    {
        $result = ExampleExtendableClass::undefinedMethod();
        $this->assertEquals('bar', $result);
    }

    public function testAccessingProtectedProperty()
    {
        $subject = new ExampleExtendableClass;
        $this->assertEmpty($subject->protectedFoo);

        $subject->protectedFoo = 'snickers';
        $this->assertEquals('bar', $subject->getProtectedFooAttribute());
    }

    /**
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExampleExtendableClass::protectedBar()
     */
    public function testAccessingProtectedMethod()
    {
        $subject = new ExampleExtendableClass;
        echo $subject->protectedBar();
    }

    /**
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExampleExtendableClass::protectedMars()
     */
    public function testAccessingProtectedStaticMethod()
    {
        echo ExampleExtendableClass::protectedMars();
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Class InvalidExtendableClass contains an invalid $implement value
     */
    public function testInvalidImplementValue()
    {
        $result = new InvalidExtendableClass;
    }

    public function testSoftImplementFake()
    {
        $result = new ExampleExtendableSoftImplementFakeClass;
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertEquals('working', $result->getStatus());
    }

    public function testSoftImplementReal()
    {
        $result = new ExampleExtendableSoftImplementRealClass;
        $this->assertTrue($result->isClassExtendedWith('ExampleBehaviorClass1'));
        $this->assertEquals('foo', $result->getFoo());
    }

    public function testSoftImplementCombo()
    {
        $result = new ExampleExtendableSoftImplementComboClass;
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertTrue($result->isClassExtendedWith('ExampleBehaviorClass1'));
        $this->assertTrue($result->isClassExtendedWith('ExampleBehaviorClass2'));
        $this->assertEquals('bar', $result->getFoo()); // ExampleBehaviorClass2 takes priority, defined last
    }
}

//
// Test classes
//

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

    protected $protectedFoo = 'bar';

    public static function vanillaIceIce()
    {
        return 'baby';
    }

    protected function protectedBar()
    {
        return 'foo';
    }

    protected static function protectedMars()
    {
        return 'bar';
    }

    public function getProtectedFooAttribute()
    {
        return $this->protectedFoo;
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

/*
 * Example class with soft implement failure
 */
class ExampleExtendableSoftImplementFakeClass extends Extendable
{
    public $implement = ['@RabbleRabbleRabble'];

    public static function getStatus()
    {
        return 'working';
    }
}

/*
 * Example class with soft implement success
 */
class ExampleExtendableSoftImplementRealClass extends Extendable
{
    public $implement = ['@ExampleBehaviorClass1'];
}

/*
 * Example class with soft implement hybrid
 */
class ExampleExtendableSoftImplementComboClass extends Extendable
{
    public $implement = [
        'ExampleBehaviorClass1',
        '@ExampleBehaviorClass2',
        '@RabbleRabbleRabble'
    ];
}
