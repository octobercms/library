<?php namespace October\Contracts\Element;

use October\Rain\Element\Form\FieldDefinition;
use October\Rain\Element\Form\FieldsetDefinition;

/**
 * FormElement
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface FormElement
{
    /**
     * addFormField adds a field to the fieldset
     */
    public function addFormField(string $fieldName = null, string $label = null): FieldDefinition;

    /**
     * getFormFieldset returns the current fieldset definition
     */
    public function getFormFieldset(): FieldsetDefinition;

    /**
     * getFormContext returns the current form context, e.g. create, update
     */
    public function getFormContext();
}
