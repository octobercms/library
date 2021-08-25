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
     * @var bool searchable specifies if this column can be searched
     */
    public $searchable = false;

    /**
     * @var bool hidden defines the column without ever displaying it
     */
    public $hidden = false;

    /**
     * @var bool sortable specifies if this column can be sorted
     */
    public $sortable = true;

    /**
     * @var string align the column, eg: left, right or center
     */
    public $align;

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
        if (isset($config['hidden'])) {
            $this->hidden();
        }
        if (isset($config['searchable'])) {
            $this->searchable();
        }
        if (isset($config['align']) && in_array($config['align'], ['left', 'right', 'center'])) {
            $this->align = $config['align'];
        }
        if (array_key_exists('sortable', $config)) {
            $this->sortable((bool) $config['sortable']);
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
     */
    public function displayAs(string $type): ColumnDefinition
    {
        $this->type = strtolower($type);

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
}
