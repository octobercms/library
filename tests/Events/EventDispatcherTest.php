<?php

use October\Rain\Events\Dispatcher;
use October\Rain\Events\FakeDispatcher;

/**
 * EventDispatcherTest
 */
class EventDispatcherTest extends TestCase
{
    /**
     * testFakerClass
     */
    public function testFakerClass(): void
    {
        Event::swap(new FakeDispatcher(new Dispatcher));

        Event::fire(EventDispatcherTest::class);

        Event::assertDispatched(EventDispatcherTest::class);
    }
}
