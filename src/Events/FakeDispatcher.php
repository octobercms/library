<?php namespace October\Rain\Events;

use Illuminate\Support\Testing\Fakes\EventFake as EventFakeBase;

/**
 * FakeDispatcher
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
class FakeDispatcher extends EventFakeBase
{
    /**
     * __construct a new event fake instance.
     */
    public function __construct(PriorityDispatcher $dispatcher, $eventsToFake = [])
    {
        parent::__construct($dispatcher->getLaravelDispatcher(), $eventsToFake);
    }

    /**
     * fire proxies to dispatch
     */
    public function fire(...$args)
    {
        return parent::dispatch(...$args);
    }
}
