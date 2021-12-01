<?php namespace October\Contracts\Database;

/**
 * HasSortableInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasSortableInterface
{
    /**
     * setSortableOrder
     */
    public function setSortableOrder($itemIds, $itemOrders = null);
}
