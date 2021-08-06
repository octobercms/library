<?php namespace October\Rain\Database\Contracts;

use Illuminate\Support\Collection;

/**
 * HasTreeInterface
 *
 * @package october\database
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
     * scopeListsNested
     */
    public function scopeListsNested($query, $column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;');
}
