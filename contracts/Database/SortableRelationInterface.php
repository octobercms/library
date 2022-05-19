<?php namespace October\Contracts\Database;

/**
 * SortableRelationInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface SortableRelationInterface
{
    /**
     * setSortableRelationOrder sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     * @param string $relationName
     * @param mixed $itemIds
     * @param array $itemOrders
     */
    public function setSortableRelationOrder($relationName, $itemIds, $itemOrders = null);

    /**
     * isSortableRelation returns true if the supplied relation is sortable.
     */
    public function isSortableRelation($relationName);
}
