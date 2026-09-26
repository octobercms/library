<?php

use October\Rain\Element\ElementBase;
use October\Rain\Element\ElementHolder;
use October\Rain\Extension\ExtensionBase;

class ElementBaseTestElement extends ElementBase
{
}

class ElementBaseTestBehavior extends ExtensionBase
{
    public function behaviorMarker()
    {
        return 'applied';
    }
}

class ElementBaseTestElementWithBehavior extends ElementBase
{
    public $implement = [ElementBaseTestBehavior::class];
}

class ElementBaseTest extends TestCase
{
    public function testExtendCallbackIsAppliedOnConstruction()
    {
        $callbacks = \October\Rain\Extension\Container::$classCallbacks;
        try {
            $seen = null;
            ElementBaseTestElement::extend(function ($element) use (&$seen) {
                $seen = [$element, $element->label];
                $element->addDynamicMethod('shout', fn () => strtoupper($element->label));
            });

            $element = new ElementBaseTestElement(['label' => 'Name']);

            $this->assertSame([$element, 'Name'], $seen);
            $this->assertSame('NAME', $element->shout());
            $this->assertArrayNotHasKey('shout', $element->config);
        }
        finally {
            \October\Rain\Extension\Container::$classCallbacks = $callbacks;
        }
    }

    public function testImplementedBehaviorIsApplied()
    {
        $element = new ElementBaseTestElementWithBehavior;

        $this->assertSame('applied', $element->behaviorMarker());
        $this->assertSame($element, $element->span('full'));
        $this->assertSame('full', $element->span);
    }

    public function testHolderReturnsWrittenValueAfterRead()
    {
        $holder = new ElementHolder(['name' => 'old']);

        $this->assertSame('old', $holder->get('name'));

        $holder['name'] = 'new';
        $this->assertSame('new', $holder->get('name'));
        $this->assertSame(['name' => 'new'], $holder->getTouchedElements());

        $holder['name'] = null;
        $this->assertNull($holder->get('name'));
        $this->assertSame(['name' => null], $holder->getTouchedElements());

        unset($holder['name']);
        $this->assertNull($holder->get('name'));
    }
}
