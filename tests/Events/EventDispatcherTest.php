<?php

use October\Rain\Events\Dispatcher;
use October\Rain\Events\FakeDispatcher;
use October\Rain\Events\PriorityDispatcher;

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
