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
     * scopeGetNested
     */
    public function scopeGetNested($query);
}
