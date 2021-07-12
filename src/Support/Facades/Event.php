<?php namespace October\Rain\Support\Facades;

use Model;
use Cache;
use October\Rain\Events\FakeDispatcher;
use Illuminate\Support\Facades\Event as EventBase;

/**
 * Event
 *
 * @see \October\Rain\Events\Dispatcher
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
}
