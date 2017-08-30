<?php

use October\Rain\Extension\Extendable;
use October\Rain\Extension\ExtensionBase;

class ExtensionTest extends TestCase
{
    public function testExtendingBehavior()
    {
        $subject = new ExtensionTest_ExampleExtendableClass;
        $this->assertEquals('foo', $subject->behaviorAttribute);

        ExtensionTest_ExampleBehaviorClass1::extend(function($extension) {
            $extension->behaviorAttribute = 'bar';
        });

        $subject = new ExtensionTest_ExampleExtendableClass;
        $this->assertEquals('bar', $subject->behaviorAttribute);
    }
}

/*
 * Example class that has extensions enabled
 */
class ExtensionTest_ExampleExtendableClass extends Extendable
{
    public $implement = ['ExtensionTest_ExampleBehaviorClass1'];
}

/**
 * Example behavior classes
 */
class ExtensionTest_ExampleBehaviorClass1 extends ExtensionBase
{
    public $behaviorAttribute = 'foo';
}
