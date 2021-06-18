<?php namespace October\Rain\Contracts\Element;

use October\Rain\Element\Form\FieldDefinition;

/**
 * FormElement
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface FormElement
{
    /**
     * addFormField
     */
    public function addFormField(string $fieldName = null, string $label = null): FieldDefinition;
}
