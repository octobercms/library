<?php namespace October\Rain\Element\Filter;

use October\Rain\Element\ElementBase;

/**
 * ScopeDefinition
 *
 * @method ScopeDefinition useConfig(array $config) useConfig applies the supplied configuration
 * @method ScopeDefinition scopeName(string $name) scopeName for this scope
 * @method ScopeDefinition label(string $label) label for this scope
 * @method ScopeDefinition value(mixed $value) current value for this scope
 * @method ScopeDefinition nameFrom(string $column) nameFrom model attribute to use for the display name
 * @method ScopeDefinition valueFrom(mixed $value) valueFrom model attribute to use for the source value
 * @method ScopeDefinition descriptionFrom(string $column) descriptionFrom column to use for the description
 * @method ScopeDefinition options(mixed $options) options for the scope
 * @method ScopeDefinition dependsOn(array $scopes) dependsOn other scopes, when the other scopes are modified, this scope will update
 * @method ScopeDefinition context(string $context) context visibility of this scope
 * @method ScopeDefinition default(mixed $value) default value for the scope
 * @method ScopeDefinition conditions(string $sql) conditions to apply in raw SQL format
 * @method ScopeDefinition scope(string $method) scope method for the model
 * @method ScopeDefinition cssClass(string $class) cssClass to attach to the scope container
 * @method ScopeDefinition emptyOption(string $label) emptyOption label for intentionally selecting an empty value (optional)
 * @method ScopeDefinition disabled(bool $value) disabled setting for the scope
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ScopeDefinition extends ElementBase
{
    /**
     * displayAs type for this scope. Supported modes are:
     * - group - filter by a group of IDs. Default.
     * - checkbox - filter by a simple toggle switch.
     */
    public function displayAs($type): ScopeDefinition
    {
        return $this->type($type ?: $this->type);
    }

    /**
     * nameFrom sets the default value for valueFrom
     */
    public function nameFrom($value): ScopeDefinition
    {
        $this->attributes['nameFrom'] = $value;

        if (!isset($this->attributes['valueFrom'])) {
            $this->attributes['valueFrom'] = $value;
        }

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
     * setScopeValue
     */
    public function setScopeValue($value)
    {
        if (is_array($value)) {
            $this->attributes = array_merge($this->attributes, $value);
        }

        $this->scopeValue($value);
    }

    /**
     * initDefaultValues for this scope
     */
    protected function initDefaultValues()
    {
        $this
            ->nameFrom('name')
            ->disabled(false)
            ->displayAs('group')
        ;
    }
}
