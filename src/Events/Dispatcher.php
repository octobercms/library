<?php namespace October\Rain\Events;

use Illuminate\Events\Dispatcher as DispatcherBase;

/**
 * Dispatcher proxy class
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
class Dispatcher extends DispatcherBase
{
    /**
     * fire proxies to dispatch
     */
    public function fire(...$args)
    {
        return parent::dispatch(...$args);
    }
}
