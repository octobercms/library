<?php namespace October\Contracts\Database;

/**
 * SortableInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface SortableInterface
{
    /**
     * setSortableOrder
     */
    public function setSortableOrder($itemIds, $itemOrders = null);
}
