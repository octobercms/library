<?php namespace October\Rain\Element\Navigation;

use October\Rain\Element\ElementBase;

/**
 * ItemDefinition
 *
 * @method ItemDefinition useConfig(array $config) useConfig applies the supplied configuration
 * @method ItemDefinition code(string $code) code for the nav item
 * @method ItemDefinition label(string $label) label for the nav item
 * @method ItemDefinition url(string $url) url address for the nav item
 * @method ItemDefinition icon(null $icon) icon to display
 * @method ItemDefinition order(int $order) order number when displaying
 * @method ItemDefinition customData(array $customData) customData to include with the nav item
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ItemDefinition extends ElementBase
{
    /**
     * initDefaultValues for this item
     */
    protected function initDefaultValues()
    {
        $this
            ->order(-1)
        ;
    }
}
