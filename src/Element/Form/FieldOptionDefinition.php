<?php namespace October\Rain\Element\Form;

use Arr;
use Html;
use October\Rain\Element\ElementBase;

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
 * @method FieldOptionDefinition cssColor(string $cssColor) cssColor defines a status indicator color for the option (dropdown)
 * @method FieldOptionDefinition icon(string $icon) icon specifies an icon name for this option
 * @method FieldOptionDefinition image(string $image) image specifies an image URL for this option
 * @method FieldOptionDefinition indentLevel(int $indentLevel) indentLevel sets the level that the option sits
 * @method FieldOptionDefinition children(array $image) children specifies child options as an alternative to indenting
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

    /**
     * useOptionConfig
     */
    public function useOptionConfig(array $option): FieldOptionDefinition
    {
        if (!is_array($option)) {
            $this->label($option);
            return $this;
        }

        if (Arr::isAssoc($option)) {
            if (isset($options['children']) && is_array($options['children'])) {
                $options['children'] = $this->evalChildOptions($options['children']);
            }

            $this->useConfig($option);
            return $this;
        }

        $firstPart = (string) ($option[0] ?? '');
        $secondPart = (string) ($option[1] ?? '');

        $this->label($firstPart);
        $this->comment($secondPart);

        if (Html::isValidColor($secondPart)) {
            $this->cssColor($secondPart);
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
            $result[$value] = (new FieldOptionDefinition)
                ->value($value)
                ->useOptionConfig($option);
        }

        return $result;
    }
}
