<?php namespace October\Rain\Element\Contracts;

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
