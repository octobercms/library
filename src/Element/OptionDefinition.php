<?php namespace October\Rain\Element;

use Arr;
use Html;
use October\Rain\Element\ElementBase;

/**
 * OptionDefinition represents a single option that can be associated to an field field
 *
 * @link https://docs.octobercms.com/3.x/element/define-options.html
 *
 * @method OptionDefinition label(string $label) label for this option
 * @method OptionDefinition comment(string $comment) comment for the form field
 * @method OptionDefinition value(string $value) value for the form option
 * @method OptionDefinition readOnly(bool $readOnly) readOnly specifies if the option is read-only or not.
 * @method OptionDefinition disabled(bool $disabled) disabled specifies if the option is disabled or not.
 * @method OptionDefinition hidden(bool $hidden) hidden defines the option without ever displaying it
 * @method OptionDefinition color(string $color) color defines a status indicator color for the option as a hex color (dropdown)
 * @method OptionDefinition icon(string $icon) icon specifies an icon name for this option
 * @method OptionDefinition image(string $image) image specifies an image URL for this option
 * @method OptionDefinition children(array $image) children specifies child options for a nested structure
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class OptionDefinition extends ElementBase
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

    /**
     * useOptionConfig
     */
    public function useOptionConfig($value, $option): OptionDefinition
    {
        $this->value($value)->label($value);

        // Option as string
        if (!is_array($option)) {
            $this->label($option);
            return $this;
        }

        // Option as definition
        if (Arr::isAssoc($option)) {
            if (isset($option['children']) && is_array($option['children'])) {
                $option['children'] = $this->evalChildOptions($option['children']);
            }

            $this->useConfig($option);
            return $this;
        }

        // Option as [label, comment]
        $firstPart = (string) ($option[0] ?? '');
        $secondPart = (string) ($option[1] ?? '');

        $this->label($firstPart);
        $this->comment($secondPart);

        if (Html::isValidColor($secondPart)) {
            $this->color($secondPart);
        }
        elseif (strpos($secondPart, '.')) {
            $this->image($secondPart);
        }
        else {
            $this->icon($secondPart);
        }

        return $this;
    }

    /**
     * evalChildOptions
     */
    protected function evalChildOptions(array $children): array
    {
        $result = [];

        foreach ($children as $value => $option) {
            $result[$value] = (new OptionDefinition)->useOptionConfig($value, $option);
        }

        return $result;
    }
}
