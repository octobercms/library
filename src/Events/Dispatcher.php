<?php namespace October\Rain\Events;

use Illuminate\Events\Dispatcher as DispatcherBase;

/**
 * Dispatcher adds the fire method to the base dispatcher
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
class Dispatcher extends DispatcherBase
{
    /**
     * fire an event and call the listeners.
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->container->make('events.global')->fire($event, $payload, $halt);
    }
}
