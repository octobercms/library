<?php namespace October\Contracts\Element;

use October\Rain\Element\Filter\ScopeDefinition;

/**
 * FilterElement
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface FilterElement
{
    /**
     * defineScope adds a scope to the filter element
     */
    public function defineScope(string $scopeName = null, string $label = null): ScopeDefinition;
}
