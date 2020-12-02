<?php namespace October\Rain\Support\Testing\Fakes;

use October\Rain\Events\Dispatcher;
use October\Rain\Support\Arr;

class EventFake extends \Illuminate\Support\Testing\Fakes\EventFake
{
    // Alias the fire() method to parent's dispatch() method
    public function fire($event, $payload = [], $halt = false)
    {
        return parent::dispatch($event, $payload, $halt);
    }
}
