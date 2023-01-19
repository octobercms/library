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
    public function __construct($dispatcher, $eventsToFake = [])
    {
        parent::__construct(
            $dispatcher instanceof PriorityDispatcher ? $dispatcher->getLaravelDispatcher() : $dispatcher,
            $eventsToFake
        );
    }

    /**
     * fire proxies to dispatch
     */
    public function fire(...$args)
    {
        return parent::dispatch(...$args);
    }
}
