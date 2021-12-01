<?php namespace October\Contracts\Database;

/**
 * HasNestedSetInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface HasNestedSetInterface
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
