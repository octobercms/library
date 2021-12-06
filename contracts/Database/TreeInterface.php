<?php namespace October\Contracts\Database;

use Illuminate\Support\Collection;

/**
 * TreeInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface TreeInterface
{
    /**
     * getChildren
     */
    public function getChildren(): Collection;

    /**
     * getChildCount
     */
    public function getChildCount(): int;

    /**
     * scopeGetNested
     */
    public function scopeGetNested($query);

    /**
     * scopeListsNested
     */
    public function scopeListsNested($query, $column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;');
}
