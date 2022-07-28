<?php namespace October\Rain\Element\Form;

use October\Rain\Element\ElementBase;
use IteratorAggregate;
use ArrayIterator;

/**
 * FieldsetDefinition
 *
 * @method FieldsetDefinition defaultTab(string $defaultTab) defaultTab is default tab label to use when none is specified
 * @method FieldsetDefinition suppressTabs(bool $suppressTabs) suppressTabs if set to TRUE, fields will not be displayed in tabs
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class FieldsetDefinition extends ElementBase implements IteratorAggregate
{
    /**
     * @var array fields is a collection of panes fields to these tabs
     */
    protected $fields = [];

    /**
     * initDefaultValues for this scope
     */
    protected function initDefaultValues()
    {
        $this
            ->defaultTab('Misc')
            ->suppressTabs(false)
        ;
    }

    /**
     * addField to the collection of tabs
     */
    public function addField($name, FieldDefinition $field)
    {
        $this->fields[$name] = $field;
    }

    /**
     * removeField from all tabs by name
     * @param string $name
     * @return boolean
     */
    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
            return true;
        }

        return false;
    }

    /**
     * hasFields returns true if any fields have been registered for these tabs
     * @return bool
     */
    public function hasFields()
    {
        return count($this->fields) > 0;
    }

    /**
     * getFields returns an array of the registered fields, includes tabs in format
     * array[tab][field]
     * @return array
     */
    public function getFields()
    {
        $fieldsTabbed = [];

        foreach ($this->fields as $name => $field) {
            $tabName = $field->tab ?: $this->defaultTab;
            $fieldsTabbed[$tabName][$name] = $field;
        }

        return $fieldsTabbed;
    }

    /**
     * getField object specified
     */
    public function getField(string $field)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field];
        }

        return null;
    }

    /**
     * getAllFields returns an array of the registered fields, without tabs
     * @return array
     */
    public function getAllFields()
    {
        return $this->fields;
    }

    /**
     * sortAllFields will sort the defined fields by their order attribute
     */
    public function sortAllFields()
    {
        uasort($this->fields, static function ($a, $b) {
            return $a->order - $b->order;
        });
    }

    /**
     * getIterator gets an iterator for the items
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(
            $this->suppressTabs
                ? $this->getAllFields()
                : $this->getFields()
        );
    }
}
