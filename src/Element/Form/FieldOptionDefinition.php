<?php namespace October\Rain\Element;

use October\Rain\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * FieldOptionDefinition represents a single option that can be associated to an field field
 *
 * @link https://docs.octobercms.com/3.x/element/define-options.html
 *
 * @method FieldOptionDefinition label(string $label) label for this option
 * @method FieldOptionDefinition comment(string $comment) comment for the form field
 * @method FieldOptionDefinition value(string $value) value for the form option
 * @method FieldOptionDefinition readOnly(bool $readOnly) readOnly specifies if the option is read-only or not.
 * @method FieldOptionDefinition disabled(bool $disabled) disabled specifies if the option is disabled or not.
 * @method FieldOptionDefinition hidden(bool $hidden) hidden defines the option without ever displaying it
 * @method FieldOptionDefinition cssColor(bool $cssColor) cssColor defines a status indicator color for the option (dropdown)
 * @method FieldOptionDefinition icon(bool $icon) icon specifies an icon name for this option
 * @method FieldOptionDefinition image(bool $image) image specifies an image URL for this option
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class FieldOptionDefinition extends ElementBase
{
    /**
     * initDefaultValues for this field
     */
    protected function initDefaultValues()
    {
        $this
            ->hidden(false)
            ->readOnly(false)
            ->disabled(false)
            ->comment('');
    }
}
