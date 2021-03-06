<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Http Block Facade
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 *
 * @see \October\Rain\Html\BlockBuilder
 */
class Block extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'block';
    }
}
