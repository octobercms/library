<?php namespace October\Rain\Element\Lists;

use October\Rain\Element\ElementBase;

/**
 * ColumnDefinition
 *
 * @method ColumnDefinition useConfig(array $config) useConfig applies the supplied configuration
 * @method ColumnDefinition columnName(string $name) columnName for this column
 * @method ColumnDefinition label(string $label) label for list column
 * @method ColumnDefinition shortLabel(string $shortLabel) shortLabel used in list headers
 * @method ColumnDefinition type(string $type) type for display mode, eg: text, number
 * @method ColumnDefinition align(string $align) align the column, eg: left, right or center
 * @method ColumnDefinition hidden(bool $hidden) hidden defines the column without ever displaying it
 * @method ColumnDefinition sortable(bool $sortable) sortable specifies if this column can be sorted
 * @method ColumnDefinition searchable(bool $searchable) searchable specifies if this column can be searched
 * @method ColumnDefinition invisible(bool $invisible) invisible is hidden in default list settings
 * @method ColumnDefinition clickable(bool $clickable) clickable disables the default click behavior when the column is clicked
 * @method ColumnDefinition order(int $order) order number when displaying
 * @method ColumnDefinition after(string $after) after places this column after another existing column name using the display order (+1)
 * @method ColumnDefinition before(string $before) before places this column before another existing column name using the display order (-1)
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ColumnDefinition extends ElementBase
{
    /**
     * initDefaultValues for this column
     */
    protected function initDefaultValues()
    {
        $this
            ->displayAs('text')
            ->hidden(false)
            ->sortable()
            ->searchable(false)
            ->invisible(false)
            ->clickable()
            ->order(-1)
        ;
    }

    /**
     * displayAs type for this column
     * @todo $config is deprecated, see useConfig
     */
    public function displayAs(string $type): ColumnDefinition
    {
        $this->type = $type;

        return $this;
    }
}
