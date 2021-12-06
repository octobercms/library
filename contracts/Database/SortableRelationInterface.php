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
     * setSortableRelationOrder
     */
    public function setSortableRelationOrder($relationName, $itemIds, $itemOrders = null);
}
