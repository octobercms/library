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
 * @method FieldDefinition options(array|callable $options) options available
 * @method FieldDefinition comment(string $comment) comment for the form field
 * @method FieldDefinition commentAbove(string $comment) commentAbove the form field
 * @method FieldDefinition commentHtml(bool $commentHtml) commentHtml if the comment is in HTML format
 * @method FieldDefinition placeholder(string $placeholder) placeholder to display when there is no value supplied
 * @method FieldDefinition order(int $order) order number when displaying
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class FieldDefinition extends ElementBase
{
    /**
     * @var callable optionsCallback
     */
    protected $optionsCallback;

    /**
     * initDefaultValues for this field
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
            ->order(-1)
        ;
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): ElementBase
    {
        parent::useConfig($config);

        // The config default proxies to defaults
        if (array_key_exists('default', $this->config)) {
            $this->defaults($this->config['default']);
        }

        return $this;
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
        if ($this->optionsCallback !== null) {
            return true;
        }

        if ($this->options !== null && is_array($this->options)) {
            return true;
        }

        return false;
    }

    /**
     * options get/set for dropdowns, radio lists and checkbox lists
     * @return array|self
     */
    public function options($value = null)
    {
        // get
        if ($value === null) {
            if ($this->optionsCallback !== null) {
                $callable = $this->optionsCallback;
                return $callable();
            }

            if (is_array($this->options)) {
                return $this->options;
            }

            return [];
        }

        // set
        if (is_callable($value)) {
            $this->optionsCallback = $value;
        }
        else {
            $this->options = $value;
        }

        return $this;
    }

    /**
     * matchesContext returns true if the field matches the supplied context
     */
    public function matchesContext($context): bool
    {
        if ($context === '*' || $this->context === null) {
            return true;
        }

        return in_array($context, (array) $this->context);
    }
}
