<?php namespace October\Contracts\Database;

/**
 * HasSortableRelationInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasSortableRelationInterface
{
    /**
     * setSortableRelationOrder
     */
    public function setSortableRelationOrder($relationName, $itemIds, $itemOrders = null);
}
