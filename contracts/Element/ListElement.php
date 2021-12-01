<?php namespace October\Contracts\Element;

use October\Rain\Element\Lists\ColumnDefinition;

/**
 * ListElement
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface ListElement
{
    /**
     * defineColumn
     */
    public function defineColumn(string $columnName = null, string $label = null): ColumnDefinition;
}
