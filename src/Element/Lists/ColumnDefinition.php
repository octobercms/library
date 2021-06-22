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
     * @var bool invisible is hidden in default list settings
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
            $this->type($config['type']);
        }
        if (isset($config['searchable'])) {
            $this->searchable = $config['searchable'];
        }
        if (isset($config['sortable'])) {
            $this->sortable = $config['sortable'];
        }
        if (isset($config['hidden'])) {
            $this->hidden();
        }
        if (isset($config['align']) && in_array($config['align'], ['left', 'right', 'center'])) {
            $this->align = $config['align'];
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
     * type for this column
     */
    public function type(string $type): ColumnDefinition
    {
        $this->type = strtolower($type);
        return $this;
    }

    /**
     * hidden hides the column from lists
     */
    public function hidden(): ColumnDefinition
    {
        $this->hidden = true;
        return $this;
    }
}
