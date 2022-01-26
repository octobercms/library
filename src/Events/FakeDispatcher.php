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
     * fire proxies to dispatch
     */
    public function fire(...$args)
    {
        return parent::dispatch(...$args);
    }
}
