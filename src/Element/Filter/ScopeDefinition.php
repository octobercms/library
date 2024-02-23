<?php namespace October\Rain\Element\Filter;

use October\Rain\Element\ElementBase;

/**
 * ScopeDefinition
 *
 * @method ScopeDefinition useConfig(array $config) useConfig applies the supplied configuration
 * @method ScopeDefinition scopeName(string $name) scopeName for this scope
 * @method ScopeDefinition label(string $label) label for this scope
 * @method ScopeDefinition shortLabel(string $shortLabel) shortLabel used in list headers
 * @method ScopeDefinition value(mixed $value) current value for this scope
 * @method ScopeDefinition nameFrom(string $column) nameFrom model attribute to use for the display name
 * @method ScopeDefinition valueFrom(mixed $value) valueFrom model attribute to use for the source value
 * @method ScopeDefinition descriptionFrom(string $column) descriptionFrom column to use for the description
 * @method ScopeDefinition options(mixed $options) options for the scope
 * @method ScopeDefinition dependsOn(array $scopes) dependsOn other scopes, when the other scopes are modified, this scope will update
 * @method ScopeDefinition context(string $context) context visibility of this scope
 * @method ScopeDefinition defaults(mixed $value) default value for the scope
 * @method ScopeDefinition conditions(string $sql) conditions to apply in raw SQL format
 * @method ScopeDefinition scope(string $method) scope method for the model
 * @method ScopeDefinition cssClass(string $class) cssClass to attach to the scope container
 * @method ScopeDefinition emptyOption(string $label) emptyOption label for intentionally selecting an empty value (optional)
 * @method ScopeDefinition disabled(bool $value) disabled setting for the scope
 * @method ScopeDefinition order(int $order) order number when displaying
 * @method ScopeDefinition after(string $after) after places this scope after another existing scope name using the display order (+1)
 * @method ScopeDefinition before(string $before) before places this scope before another existing scope name using the display order (-1)
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ScopeDefinition extends ElementBase
{
    /**
     * initDefaultValues for this scope
     */
    protected function initDefaultValues()
    {
        $this
            ->displayAs('group')
            ->nameFrom('name')
            ->disabled(false)
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
     * displayAs type for this scope. Supported modes are:
     * - group - filter by a group of IDs. Default.
     * - checkbox - filter by a simple toggle switch.
     */
    public function displayAs(string $type): ScopeDefinition
    {
        $this->type($type);

        return $this;
    }

    /**
     * hasOptions returns true if options have been specified
     */
    public function hasOptions(): bool
    {
        return $this->options !== null &&
            (is_array($this->options) || is_callable($this->options));
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

        $this->config['options'] = $value;

        return $this;
    }

    /**
     * setScopeValue and merge the values as config
     */
    public function setScopeValue($value)
    {
        if (is_array($value)) {
            $this->config = array_merge($this->config, $value);
        }

        $this->scopeValue($value);
    }
}
