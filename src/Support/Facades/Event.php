<?php namespace October\Rain\Support\Facades;

use Cache;
use Illuminate\Support\Facades\Event as EventBase;
use Model;
use October\Rain\Events\FakeDispatcher;

/**
 * Event
 *
 * @see \October\Rain\Events\PriorityDispatcher
 */
class Event extends EventBase
{
    /**
     * fake the instance
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new FakeDispatcher(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
        Cache::refreshEventDispatcher();

        return $fake;
    }

    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'events.priority';
    }
}
