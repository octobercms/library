<?php namespace October\Rain\Element\Contracts;

use Illuminate\Support\Collection;

/**
 * TreeItemElement
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface TreeItemElement
{
    /**
     * getChildren
     */
    public function getChildren(): Collection;

    /**
     * getChildCount
     */
    public function getChildCount(): int;
}
