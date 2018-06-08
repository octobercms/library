<?php

use October\Rain\Extension\Extendable;
use October\Rain\Extension\ExtensionBase;

class ExtendableTest extends TestCase
{
    public function testExtendingExtendableClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $this->assertNull($subject->classAttribute);

        ExtendableTest_ExampleExtendableClass::extend(function($extension) {
            $extension->classAttribute = 'bar';
        });

        $subject = new ExtendableTest_ExampleExtendableClass;
        $this->assertEquals('bar', $subject->classAttribute);
    }

    public function testSettingDeclaredPropertyOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->classAttribute = 'Test';
        $this->assertEquals('Test', $subject->classAttribute);
    }

    public function testSettingUndeclaredPropertyOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->newAttribute = 'Test';
        $this->assertNull($subject->newAttribute);
        $this->assertFalse(property_exists($subject, 'newAttribute'));
    }

    public function testSettingDeclaredPropertyOnBehavior()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $behavior = $subject->getClassExtension('ExtendableTest_ExampleBehaviorClass1');

        $subject->behaviorAttribute = 'Test';
        $this->assertEquals('Test', $subject->behaviorAttribute);
        $this->assertEquals('Test', $behavior->behaviorAttribute);
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass1'));
    }

    public function testDynamicPropertyOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $this->assertFalse(property_exists($subject, 'newAttribute'));
        $subject->addDynamicProperty('dynamicAttribute', 'Test');
        $this->assertEquals('Test', $subject->dynamicAttribute);
        $this->assertTrue(property_exists($subject, 'dynamicAttribute'));
    }

    public function testDynamicallyExtendingClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->extendClassWith('ExtendableTest_ExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass2'));
    }

    public function testDynamicMethodOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->addDynamicMethod('getFooAnotherWay', 'getFoo', 'ExtendableTest_ExampleBehaviorClass1');

        $this->assertEquals('foo', $subject->getFoo());
        $this->assertEquals('foo', $subject->getFooAnotherWay());
    }

    public function testDynamicExtendAndMethodOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->extendClassWith('ExtendableTest_ExampleBehaviorClass2');
        $subject->addDynamicMethod('getOriginalFoo', 'getFoo', 'ExtendableTest_ExampleBehaviorClass1');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass2'));
        $this->assertEquals('bar', $subject->getFoo());
        $this->assertEquals('foo', $subject->getOriginalFoo());
    }

    public function testDynamicClosureOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->addDynamicMethod('sayHello', function() {
            return 'Hello world';
        });

        $this->assertEquals('Hello world', $subject->sayHello());
    }

    public function testDynamicCallableOnClass()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $subject->addDynamicMethod('getAppName', ['ExtendableTest_ExampleClass', 'getName']);

        $this->assertEquals('october', $subject->getAppName());
    }

    public function testCallingStaticMethod()
    {
        $result = ExtendableTest_ExampleExtendableClass::getStaticBar();
        $this->assertEquals('bar', $result);

        $result = ExtendableTest_ExampleExtendableClass::vanillaIceIce();
        $this->assertEquals('baby', $result);
    }

    /**
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExtendableTest_ExampleExtendableClass::undefinedMethod()
     */
    public function testCallingUndefinedStaticMethod()
    {
        $result = ExtendableTest_ExampleExtendableClass::undefinedMethod();
        $this->assertEquals('bar', $result);
    }

    public function testAccessingProtectedProperty()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        $this->assertEmpty($subject->protectedFoo);

        $subject->protectedFoo = 'snickers';
        $this->assertEquals('bar', $subject->getProtectedFooAttribute());
    }

    /**
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExtendableTest_ExampleExtendableClass::protectedBar()
     */
    public function testAccessingProtectedMethod()
    {
        $subject = new ExtendableTest_ExampleExtendableClass;
        echo $subject->protectedBar();
    }

    /**
     * @expectedException        BadMethodCallException
     * @expectedExceptionMessage Call to undefined method ExtendableTest_ExampleExtendableClass::protectedMars()
     */
    public function testAccessingProtectedStaticMethod()
    {
        echo ExtendableTest_ExampleExtendableClass::protectedMars();
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Class ExtendableTest_InvalidExtendableClass contains an invalid $implement value
     */
    public function testInvalidImplementValue()
    {
        $result = new ExtendableTest_InvalidExtendableClass;
    }

    public function testSoftImplementFake()
    {
        $result = new ExtendableTest_ExampleExtendableSoftImplementFakeClass;
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertEquals('working', $result->getStatus());
    }

    public function testSoftImplementReal()
    {
        $result = new ExtendableTest_ExampleExtendableSoftImplementRealClass;
        $this->assertTrue($result->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass1'));
        $this->assertEquals('foo', $result->getFoo());
    }

    public function testSoftImplementCombo()
    {
        $result = new ExtendableTest_ExampleExtendableSoftImplementComboClass;
        $this->assertFalse($result->isClassExtendedWith('RabbleRabbleRabble'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass1'));
        $this->assertTrue($result->isClassExtendedWith('ExtendableTest_ExampleBehaviorClass2'));
        $this->assertEquals('bar', $result->getFoo()); // ExtendableTest_ExampleBehaviorClass2 takes priority, defined last
    }

    public function testDotNotation()
    {
        $subject = new ExtendableTest_ExampleExtendableClassDotNotation();
        $subject->extendClassWith('ExtendableTest.ExampleBehaviorClass2');

        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass1'));
        $this->assertTrue($subject->isClassExtendedWith('ExtendableTest.ExampleBehaviorClass2'));
    }
}

//
// Test classes
//

/**
 * Example behavior classes
 */
class ExtendableTest_ExampleBehaviorClass1 extends ExtensionBase
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

class ExtendableTest_ExampleBehaviorClass2 extends ExtensionBase
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
class ExtendableTest_InvalidExtendableClass extends Extendable
{
    public $implement = 24;

    public $classAttribute;
}

/*
 * Example class that has extensions enabled
 */
class ExtendableTest_ExampleExtendableClass extends Extendable
{
    public $implement = ['ExtendableTest_ExampleBehaviorClass1'];

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
class ExtendableTest_ExampleClass
{
    public static function getName()
    {
        return 'october';
    }
}

/*
 * Example class with soft implement failure
 */
class ExtendableTest_ExampleExtendableSoftImplementFakeClass extends Extendable
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
class ExtendableTest_ExampleExtendableSoftImplementRealClass extends Extendable
{
    public $implement = ['@ExtendableTest_ExampleBehaviorClass1'];
}

/*
 * Example class with soft implement hybrid
 */
class ExtendableTest_ExampleExtendableSoftImplementComboClass extends Extendable
{
    public $implement = [
        'ExtendableTest_ExampleBehaviorClass1',
        '@ExtendableTest_ExampleBehaviorClass2',
        '@RabbleRabbleRabble'
    ];
}

/*
 * Example class that has extensions enabled using dot notation
 */
class ExtendableTest_ExampleExtendableClassDotNotation extends Extendable
{
    public $implement = ['ExtendableTest.ExampleBehaviorClass1'];

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

/*
 * Add namespaced aliases for dot notation test
 */
class_alias('ExtendableTest_ExampleBehaviorClass1', 'ExtendableTest\\ExampleBehaviorClass1');
class_alias('ExtendableTest_ExampleBehaviorClass2', 'ExtendableTest\\ExampleBehaviorClass2');