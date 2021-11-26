<?php namespace October\Rain\Database\Contracts;

/**
 * HasSortableRelationInterface
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasSortableRelationInterface
{
    /**
     * setSortableRelationOrder
     */
    public function setSortableRelationOrder($relationName, $itemIds, $itemOrders = null);
}
