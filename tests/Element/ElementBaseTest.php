<?php

use October\Rain\Element\ElementBase;
use October\Rain\Element\ElementHolder;
use October\Rain\Extension\ExtensionBase;

class ElementBaseTestElement extends ElementBase
{
}

class ElementBaseTestBehavior extends ExtensionBase
{
    public $behaviorMarker = 'applied';
}

class ElementBaseTestElementWithBehavior extends ElementBase
{
    public $implement = [ElementBaseTestBehavior::class];
}

class ElementBaseTest extends TestCase
{
    public function testExtendCallbackIsAppliedOnConstruction()
    {
        $seen = null;
        ElementBaseTestElement::extend(function ($element) use (&$seen) {
            $seen = $element;
        });

        $element = new ElementBaseTestElement(['label' => 'Name']);

        $this->assertSame($element, $seen);
        $this->assertSame('Name', $element->label);
    }

    public function testImplementedBehaviorIsApplied()
    {
        $element = new ElementBaseTestElementWithBehavior;

        $this->assertTrue($element->isClassExtendedWith(ElementBaseTestBehavior::class));
        $this->assertSame('applied', $element->asExtension(ElementBaseTestBehavior::class)->behaviorMarker);
    }

    public function testHolderReturnsWrittenValueAfterRead()
    {
        $holder = new ElementHolder(['name' => 'old']);

        $this->assertSame('old', $holder->get('name'));

        $holder['name'] = 'new';
        $this->assertSame('new', $holder->get('name'));
        $this->assertSame(['name' => 'new'], $holder->getTouchedElements());

        unset($holder['name']);
        $this->assertNull($holder->get('name'));
        $this->assertSame([], $holder->getTouchedElements());
    }
}
