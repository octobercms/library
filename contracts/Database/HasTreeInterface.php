<?php namespace October\Contracts\Database;

use Illuminate\Support\Collection;

/**
 * HasTreeInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasTreeInterface
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
