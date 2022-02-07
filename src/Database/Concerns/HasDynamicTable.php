<?php namespace October\Rain\Database\Concerns;

/**
 * HasDynamicTable for a model
 */
trait HasDynamicTable
{
    /**
     * @var bool dynamicTable support for morphed relations
     */
    protected $dynamicTable = false;

    /**
     * hasDynamicTable
     */
    public function hasDynamicTable(): bool
    {
        return (bool) $this->dynamicTable;
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass()
    {
        return $this->hasDynamicTable()
            ? parent::getMorphClass() . '@' . $this->getTable()
            : parent::getMorphClass();
    }
}
