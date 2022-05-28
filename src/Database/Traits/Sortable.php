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
                $this->setSortableOrder([$this->getKey()], [$this->getKey()]);
            }
        });
    }

    /**
     * setSortableOrder sets the sort order of records to the specified orders, suppling
     * a reference pool of sorted values.
     * @param  mixed $itemIds
     * @param  array $availableOrderRefs
     * @return void
     */
    public function setSortableOrder($itemIds, $referencePool = null)
    {
        if (!is_array($itemIds)) {
            return;
        }

        if ($referencePool && !is_array($referencePool)) {
            return;
        }

        $sortKeyMap = $this->processSortableOrdersInternal($itemIds, $referencePool);
        if (count($itemIds) !== count($sortKeyMap)) {
            throw new Exception('Invalid setSortableOrder call - count of itemIds do not match count of referencePool');
        }

        $upsert = [];
        foreach ($itemIds as $id) {
            $sortOrder = $sortKeyMap[$id] ?? null;
            if ($sortOrder !== null) {
                $upsert[] = ['id' => $id, 'sort_order' => $sortOrder];
            }
        }

        if ($upsert) {
            foreach ($upsert as $update) {
                $this->newQuery()->where($this->getKeyName(), $update['id'])->update([$this->getSortOrderColumn() => $update['sort_order']]);
            }
        }
    }

    /**
     * processSortableOrdersInternal
     */
    protected function processSortableOrdersInternal($itemIds, $referencePool = null): array
    {
        // Extract a reference pool from the database
        if (!$referencePool) {
            $referencePool = $this->newQuery()
                ->whereIn($this->getKeyName(), $itemIds)
                ->pluck($this->getSortOrderColumn())
                ->all();
        }

        // Check for corrupt values, if found, reset with a unique pool
        $countRefPool = count($referencePool);
        if (
            $countRefPool !== count(array_unique($referencePool)) ||
            $countRefPool !== count(array_filter($referencePool))
        ) {
            $referencePool = $itemIds;
        }

        // Sort pool to apply against the sorted items
        sort($referencePool);

        // Process the item orders to a sort key map
        $result = [];
        foreach ($itemIds as $index => $id) {
            $result[$id] = $referencePool[$index];
        }

        return $result;
    }

    /**
     * resetSortableOrdering can be used to repair corrupt or missing sortable definitions.
     */
    public function resetSortableOrdering()
    {
        $ids = $this->newQuery()->pluck($this->getKeyName());

        foreach ($ids as $id) {
            $this->newQuery()->where($this->getKeyName(), $id)->update([$this->getSortOrderColumn() => $id]);
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
