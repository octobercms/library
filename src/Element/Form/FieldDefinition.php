<?php namespace October\Rain\Element\Form;

/**
 * FieldDefinition
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class FieldDefinition
{
    /**
     * @var string fieldName
     */
    public $fieldName;

    /**
     * @var string label for this field
     */
    public $label;

    /**
     * @var string type for display mode, eg: text, textarea
     */
    public $type = 'text';

    /**
     * @var bool hidden defines the field without ever displaying it
     */
    public $hidden = false;

    /**
     * @var string tab this field belongs to
     */
    public $tab;

    /**
     * @var string span specifies the field size and side, eg: auto, left, right, full
     */
    public $span = 'full';

    /**
     * @var string spanClass is used by the row span type for a custom css class
     */
    public $spanClass = '';

    /**
     * @var string size for the field, eg: tiny, small, large, huge, giant
     */
    public $size = 'large';

    /**
     * @var string options available
     */
    public $options;

    /**
     * @var string comment to accompany the field
     */
    public $comment = '';

    /**
     * @var string placeholder to display when there is no value supplied
     */
    public $placeholder = '';

    /**
     * @var string commentPosition
     */
    public $commentPosition = 'below';

    /**
     * @var string commentHtml if the comment is in HTML format
     */
    public $commentHtml = false;

    /**
     * @var array config in raw format, if supplied.
     */
    public $config;

    /**
     * __construct
     */
    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * evalConfig from an array and apply them to the object
     */
    protected function evalConfig(array $config): void
    {
        if (isset($config['label'])) {
            $this->label($config['label']);
        }
        if (isset($config['type'])) {
            $this->displayAs($config['type']);
        }
        if (isset($config['hidden'])) {
            $this->hidden((bool) $config['hidden']);
        }
        if (isset($config['tab'])) {
            $this->tab($config['tab']);
        }
        if (isset($config['span'])) {
            $this->span($config['span'], $config['spanClass'] ?? '');
        }
        if (isset($config['size'])) {
            $this->size($config['size']);
        }
        if (isset($config['options'])) {
            $this->options($config['options']);
        }
        if (isset($config['commentAbove'])) {
            $this->comment($config['commentAbove'], 'above');
        }
        if (isset($config['comment'])) {
            $this->comment($config['comment']);
        }
        if (isset($config['placeholder'])) {
            $this->placeholder($config['placeholder']);
        }
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): FieldDefinition
    {
        $this->config = $config;

        $this->evalConfig($config);

        return $this;
    }

    /**
     * label for this field
     */
    public function label(string $label): FieldDefinition
    {
        $this->label = $label;

        return $this;
    }

    /**
     * displayAs type for this field
     */
    public function displayAs(string $type): FieldDefinition
    {
        $this->type = strtolower($type);

        return $this;
    }

    /**
     * hidden hides the column from lists
     */
    public function hidden(bool $hidden = true): FieldDefinition
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * tab this field belongs to
     */
    public function tab(string $value): FieldDefinition
    {
        $this->tab = $value;

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
     * size of the form field
     */
    public function size(string $value = 'large'): FieldDefinition
    {
        $this->size = $value;

        return $this;
    }

    /**
     * options for dropdowns, radio lists and checkbox lists
     */
    public function options($value = null)
    {
        if ($value === null) {
            if (is_array($this->options)) {
                return $this->options;
            }
            elseif (is_callable($this->options)) {
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

    /**
     * placeholder text shown when the field is empty
     */
    public function placeholder(string $text): FieldDefinition
    {
        $this->placeholder = $text;

        return $this;
    }
}
