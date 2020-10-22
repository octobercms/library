<?php namespace October\Rain\Database\Behaviors;

use Exception;
use October\Rain\Database\SortableScope;
use October\Rain\Extension\ExtensionBase;

/**
 * Sortable model behavior
 *
 * Usage:
 *
 * Model table must have sort_order table column.
 *
 * In the model class definition:
 *
 *   public $implement = ['\October\Rain\Database\Traits\Sortable'];
 *
 * To set orders:
 *
 *   $model->setSortableOrder($recordIds, $recordOrders);
 *
 * You can change the sort field used by setting:
 *
 *   $model->sort_order = 'my_sort_order';
 *
 */
class Sortable extends ExtensionBase
{
    protected $model;

    public function __construct($parent)
    {
        $this->model = $parent;
        $this->bootSortable();
    }

    /**
     * Boot the sortable behavior for this model.
     *
     * @return void
     */
    public function bootSortable()
    {
        $self = $this;
        $model = $this->model;
        $class = get_class($model);

        $class::created(function ($model) use ($self) {
            $sortOrderColumn = $self->getSortOrderColumn();

            if (is_null($model->$sortOrderColumn)) {
                $self->setSortableOrder($model->getKey());
            }
        });

        $class::addGlobalScope(new SortableScope);
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     *
     * @param  mixed $itemIds
     * @param  array $itemOrders
     * @return void
     */
    public function setSortableOrder($itemIds, $itemOrders = null)
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }

        if ($itemOrders === null) {
            $itemOrders = $itemIds;
        }

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setSortableOrder call - count of itemIds do not match count of itemOrders');
        }

        $model = $this->model;
        $sortOrderColumn = $model->getSortOrderColumn();

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $model->newQuery()->where($model->getKeyName(), $id)->update([$sortOrderColumn => $order]);
        }
    }

    /**
     * Get the name of the "sort order" column.
     *
     * @return string
     */
    public function getSortOrderColumn() {
        $class = get_class($this->model);
        return defined($class.'::SORT_ORDER') ? $class::SORT_ORDER : 'sort_order';
    }
}
