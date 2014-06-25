<?php

use October\Rain\Extension\Extendable;

class ExampleExtendableClass extends Extendable
{
    public $declaredAttribute;
    
    public function setDeclaredAttribute()
    {
        $this->declaredAttribute = "Test";
    }

    public function setAnUndeclaredAttribute()
    {
        $this->newAttribute = "Test";
    }
}

class ExtendableSetTest extends TestCase
{
    protected $subject;
    
    public function setUp()
    {
        $this->subject = new ExampleExtendableClass();
    }

    public function testSettingDeclaredAttributeOnClass()
    {
        $this->subject->setDeclaredAttribute();
        
        $this->assertEquals("Test", $this->subject->declaredAttribute);
    }

    public function testSettingUninitilaizedAttributeOnClass()
    {
        $this->subject->setAnUndeclaredAttribute();
        
        $this->assertEquals("Test", $this->subject->newAttribute);
    }
}