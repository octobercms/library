<?php namespace October\Rain\Database\Contracts;

/**
 * HasNestedSetInterface
 *
 * @package october\database
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

    /**
     * makeRoot
     */
    public function makeRoot();
}
