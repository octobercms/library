<?php

use October\Rain\Events\Dispatcher;
use October\Rain\Events\PriorityDispatcher;
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
        $dispatcher = new PriorityDispatcher;

        $dispatcher->setLaravelDispatcher(new Dispatcher);

        Event::swap(new FakeDispatcher($dispatcher));

        Event::fire(EventDispatcherTest::class);

        Event::assertDispatched(EventDispatcherTest::class);
    }
}
