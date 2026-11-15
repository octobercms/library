<?php namespace October\Rain\Support\Facades;

use Model;
use Cache;
use October\Rain\Events\FakeDispatcher;
use Illuminate\Support\Facades\Event as EventBase;

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
        $actualDispatcher = static::isFake()
            ? static::getFacadeRoot()->dispatcher
            : static::getFacadeRoot();

        static::swap($fake = new FakeDispatcher($actualDispatcher, $eventsToFake));

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
