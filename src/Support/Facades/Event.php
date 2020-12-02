<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Cache;
use October\Rain\Database\Model;
use October\Rain\Support\Testing\Fakes\EventFake;

/**
 * @see \Illuminate\Support\Facades\Event
 * @see \October\Rain\Support\Testing\Fakes\EventFake
 */
class Event extends \Illuminate\Support\Facades\Event
{
    /**
     * Replace the bound instance with a fake.
     *
     * @param  array|string  $eventsToFake
     * @return \October\Rain\Support\Testing\Fakes\EventFake
     */
    public static function fake($eventsToFake = [])
    {
        static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));

        Model::setEventDispatcher($fake);
        Cache::refreshEventDispatcher();

        return $fake;
    }
}
