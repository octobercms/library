<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Scopes\SortableScope;
use Exception;

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
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Sortable
{
    /**
     * bootSortable trait for this model.
     */
    public static function bootSortable()
    {
        static::addGlobalScope(new SortableScope);
    }

    /**
     * initializeSortable trait for this model.
     */
    public function initializeSortable()
    {
        $this->bindEvent('model.afterCreate', function () {
            $sortOrderColumn = $this->getSortOrderColumn();

            if (is_null($this->$sortOrderColumn)) {
                $this->setSortableOrder($this->getKey());
            }
        });
    }

    /**
     * setSortableOrder sets the sort order of records to the specified orders. If the
     * orders is undefined, the record identifier is used.
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

        if (count($itemIds) !== count($itemOrders)) {
            throw new Exception('Invalid setSortableOrder call - count of itemIds do not match count of itemOrders');
        }

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $this->newQuery()->where($this->getKeyName(), $id)->update([$this->getSortOrderColumn() => $order]);
        }
    }

    /**
     * getSortOrderColumn name of the "sort order" column.
     * @return string
     */
    public function getSortOrderColumn()
    {
        return defined('static::SORT_ORDER') ? static::SORT_ORDER : 'sort_order';
    }

    /**
     * getQualifiedSortOrderColumn gets the fully qualified "sort order" column.
     * @return string
     */
    public function getQualifiedSortOrderColumn()
    {
        return $this->getTable().'.'.$this->getSortOrderColumn();
    }
}
