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
 * You can change the sort field used by declaring this in your model:
 *
 *   const SORT_ORDER = 'my_sort_order';
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
        $this->model::created(function ($model) {
            $sortOrderColumn = $model->getSortOrderColumn();

            if (is_null($model->$sortOrderColumn)) {
                $model->setSortableOrder($model->getKey());
            }
        });

        $this->model::addGlobalScope(new SortableScope);
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

        $sortOrderColumn = $this->model->getSortOrderColumn();

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $this->model->newQuery()->where($this->model->getKeyName(), $id)->update([$sortOrderColumn => $order]);
        }
    }

    /**
     * Get the name of the "sort order" column.
     *
     * @return string
     */
    public function getSortOrderColumn()
    {
        if (defined(get_class($this->model).'::SORT_ORDER')) {
            $column = $this->model::SORT_ORDER;
        } else if (isset($this->model->sort_order_column)) {
            $column = $this->model->sort_order_column;
        } else {
            $column = 'sort_order';
        }
        return $column;
    }
}
