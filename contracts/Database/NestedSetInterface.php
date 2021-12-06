<?php namespace October\Contracts\Database;

/**
 * NestedSetInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface NestedSetInterface
{
    /**
     * moveAfter
     */
    public function moveAfter($node);

    /**
     * moveBefore
     */
    public function moveBefore($node);

    /**
     * makeChildOf
     */
    public function makeChildOf($node);
}
