<?php namespace October\Rain\Database\Contracts;

/**
 * HasSortableInterface
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasSortableInterface
{
    /**
     * setSortableOrder
     */
    public function setSortableOrder($itemIds, $itemOrders = null);
}
