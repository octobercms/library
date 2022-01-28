<?php namespace October\Rain\Element\Lists;

/**
 * ColumnDefinition
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ColumnDefinition
{
    /**
     * @var string columnName within the list
     */
    public $columnName;

    /**
     * @var string label for list column
     */
    public $label;

    /**
     * @var string type for display mode, eg: text, number
     */
    public $type = 'text';

    /**
     * @var string align the column, eg: left, right or center
     */
    public $align;

    /**
     * @var bool hidden defines the column without ever displaying it
     */
    public $hidden = false;

    /**
     * @var bool sortable specifies if this column can be sorted
     */
    public $sortable = true;

    /**
     * @var bool searchable specifies if this column can be searched
     */
    public $searchable = false;

    /**
     * @var bool invisible is hidden in default list settings
     */
    public $invisible = false;

    /**
     * @var bool clickable disables the default click behavior when the column is clicked
     */
    public $clickable = true;

    /**
     * @var array config in raw format, if supplied.
     */
    public $config;

    /**
     * __construct the column
     */
    public function __construct(string $columnName)
    {
        $this->columnName = $columnName;
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
        if (isset($config['align'])) {
            $this->align((string) $config['align']);
        }
        if (isset($config['hidden'])) {
            $this->hidden((bool) $config['hidden']);
        }
        if (isset($config['sortable'])) {
            $this->sortable((bool) $config['sortable']);
        }
        if (isset($config['searchable'])) {
            $this->searchable((bool) $config['searchable']);
        }
        if (isset($config['invisible'])) {
            $this->invisible((bool) $config['invisible']);
        }
        if (isset($config['clickable'])) {
            $this->clickable((bool) $config['clickable']);
        }
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): ColumnDefinition
    {
        $this->config = $config;

        $this->evalConfig($config);

        return $this;
    }

    /**
     * label for this column
     */
    public function label(string $label): ColumnDefinition
    {
        $this->label = $label;

        return $this;
    }

    /**
     * displayAs type for this column
     * @todo $config is deprecated, see useConfig
     */
    public function displayAs(string $type): ColumnDefinition
    {
        $this->type = strtolower($type);

        return $this;
    }

    /**
     * align specifies the column text alignment, eg: left, right, center
     */
    public function align(string $align = ''): ColumnDefinition
    {
        if (in_array($align, ['', 'left', 'right', 'center'])) {
            $this->align = $align;
        }

        return $this;
    }

    /**
     * hidden hides the column from lists
     */
    public function hidden(bool $hidden = true): ColumnDefinition
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * sortable determines if column can be sorted
     */
    public function sortable(bool $sortable = true): ColumnDefinition
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * searchable determines if column can be searched
     */
    public function searchable(bool $searchable = true): ColumnDefinition
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * invisible hides the column from lists with default list settings
     */
    public function invisible(bool $invisible = true): ColumnDefinition
    {
        $this->invisible = $invisible;

        return $this;
    }

    /**
     * clickable determines if the column row can be clicked
     */
    public function clickable(bool $clickable = true): ColumnDefinition
    {
        $this->clickable = $clickable;

        return $this;
    }
}
