<?php

namespace October\Rain\Support\Facades;

use October\Rain\Database\Model;
use October\Rain\Support\Testing\Fakes\EventFake;
use Illuminate\Support\Facades\Cache;

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
