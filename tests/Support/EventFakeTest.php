<?php

use October\Rain\Events\Dispatcher;
use October\Rain\Support\Testing\Fakes\EventFake;

class EventFakeTest extends TestCase
{
    public function setUp(): void
    {
        $this->faker = new EventFake(new Dispatcher);
    }

    public function testFire()
    {
        $event = 'event.fake.test';

        $this->faker->fire($event);
        $this->faker->assertDispatched($event);
    }
}
