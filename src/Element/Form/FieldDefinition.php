<?php namespace October\Rain\Element\Form;

use October\Rain\Element\ElementBase;

/**
 * FieldDefinition
 *
 * @method FieldDefinition useConfig(array $config) useConfig applies the supplied configuration
 * @method FieldDefinition fieldName(string $name) fieldName for this field
 * @method FieldDefinition label(string $label) label for this field
 * @method FieldDefinition value(string $value) value for the form field
 * @method FieldDefinition valueFrom(string $valueFrom) valueFrom model attribute to use for the display value.
 * @method FieldDefinition defaults(string $defaults) defaults specifies a default value for supported fields.
 * @method FieldDefinition defaultFrom(string $defaultFrom) defaultFrom model attribute to use for the default value.
 * @method FieldDefinition type(string $type) type for display mode, eg: text, textarea
 * @method FieldDefinition autoFocus(bool $autoFocus) autoFocus flags the field to be focused on load.
 * @method FieldDefinition readOnly(bool $readOnly) readOnly specifies if the field is read-only or not.
 * @method FieldDefinition disabled(bool $disabled) disabled specifies if the field is disabled or not.
 * @method FieldDefinition hidden(bool $hidden) hidden defines the field without ever displaying it
 * @method FieldDefinition tab(string $tab) tab this field belongs to
 * @method FieldDefinition span(string $span, string $spanClass) span specifies the field size and side, eg: auto, left, right, full
 * @method FieldDefinition spanClass(string $spanClass) spanClass is used by the row span type for a custom css class
 * @method FieldDefinition size(string $size) size for the field, eg: tiny, small, large, huge, giant
 * @method FieldDefinition options(string $options) options available
 * @method FieldDefinition comment(string $comment, string $position, bool $isHtml) comment to accompany the field
 * @method FieldDefinition placeholder(string $placeholder) placeholder to display when there is no value supplied
 * @method FieldDefinition commentPosition(string $commentPosition) commentPosition
 * @method FieldDefinition commentHtml(string $commentHtml) commentHtml if the comment is in HTML format
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class FieldDefinition extends ElementBase
{
    /**
     * initDefaultValues for this scope
     */
    protected function initDefaultValues()
    {
        $this
            ->hidden(false)
            ->autoFocus(false)
            ->readOnly(false)
            ->disabled(false)
            ->displayAs('text')
            ->span('full')
            ->size('large')
            ->commentPosition('below')
            ->commentHtml(false)
            ->spanClass('')
            ->comment('')
            ->placeholder('')
        ;
    }
    /**
     * displayAs type for this field
     */
    public function displayAs(string $type): FieldDefinition
    {
        $this->type($type);

        return $this;
    }

    /**
     * fieldName sets the default value for valueFrom
     */
    public function fieldName($value): FieldDefinition
    {
        $this->config['fieldName'] = $value;

        if (!isset($this->config['valueFrom'])) {
            $this->config['valueFrom'] = $value;
        }

        return $this;
    }

    /**
     * span sets a side of the field on a form
     */
    public function span(string $value = 'full', string $spanClass = ''): FieldDefinition
    {
        $this->span = $value;

        $this->spanClass = $spanClass;

        return $this;
    }

    /**
     * hasOptions returns true if options have been specified
     */
    public function hasOptions(): bool
    {
        return $this->options !== null;
    }

    /**
     * options get/set for dropdowns, radio lists and checkbox lists
     * @return array|self
     */
    public function options($value = null)
    {
        if ($value === null) {
            if (is_array($this->options)) {
                return $this->options;
            }

            if (is_callable($this->options)) {
                $callable = $this->options;
                return $callable();
            }

            return [];
        }

        $this->options = $value;

        return $this;
    }

    /**
     * comment text above or below the field
     */
    public function comment(string $text, string $position = 'below', bool $isHtml = null): FieldDefinition
    {
        $this->comment = $text;
        $this->commentPosition = $position;

        if ($isHtml !== null) {
            $this->commentHtml = $isHtml;
        }

        return $this;
    }
}
