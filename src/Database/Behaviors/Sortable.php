<?php namespace October\Rain\Database\Behaviors;

use Exception;
use October\Rain\Database\SortableScope;

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
class Sortable extends \October\Rain\Extension\ExtensionBase
{
    protected $model;

    public function __construct($parent)
    {
        $this->model = $parent;
        $this->bootSortable();
    }

    public function bootSortable()
    {
        $model = $this->model;
        $model->addDynamicMethod('getSortOrderColumn', function () use ($model) {
            return isset($model->sort_order) ? $model->sort_order : 'sort_order';
        });

        $sortOrderColumn = $model->getSortOrderColumn();

        if (is_null($model->$sortOrderColumn)) {
            $this->setSortableOrder($model->getKey());
        }

        $model->addGlobalScope(new SortableScope);
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

        $model = $this->model;
        $sortOrderColumn = $model->getSortOrderColumn();
        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $model->newQuery()->where($model->getKeyName(), $id)->update([$sortOrderColumn => $order]);
        }
    }
}
