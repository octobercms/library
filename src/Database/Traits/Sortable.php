<?php namespace October\Rain\Database\Traits;

use Exception;
use October\Rain\Database\SortableScope;

/**
 * Sortable model trait
 *
 * Usage:
 *
 * Model table must have sort_order table column.
 *
 * In the model class definition:
 *
 *   use \October\Rain\Database\Traits\Sortable;
 *
 * To set orders:
 *
 *   $model->setSortableOrder($recordIds, $recordOrders);
 *
 * You can change the sort field used by declaring:
 *
 *   const SORT_ORDER = 'my_sort_order';
 *
 */
trait Sortable
{
    /**
     * Boot the sortable trait for this model.
     * @return void
     */
    public static function bootSortable()
    {
        static::created(function($model) {
            $sortOrderColumn = $model->getSortOrderColumn();

            if (is_null($model->$sortOrderColumn)) {
                $model->setSortableOrder($model->getKey());
            }
        });

        static::addGlobalScope(new SortableScope);
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
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

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $this->newQuery()->where($this->getKeyName(), $id)->update([$this->getSortOrderColumn() => $order]);
        }
    }

    /**
     * Get the name of the "sort order" column.
     * @return string
     */
    public function getSortOrderColumn()
    {
        return defined('static::SORT_ORDER') ? static::SORT_ORDER : 'sort_order';
    }
}
