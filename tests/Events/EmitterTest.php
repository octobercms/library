<?php

use October\Rain\Events\Emitter;

class EmitterTest extends TestCase
{

    public function testBind()
    {
        $emitter = new Emitter;
        $result = false;

        $emitter->trigger('event.test');
        $this->assertEquals(false, $result);

        $emitter->bind('event.test', function() use (&$result) {
            $result = true;
        });

        $emitter->trigger('event.test');
        $this->assertEquals(true, $result);
    }
    
    public function testBindOnce()
    {
        $emitter = new Emitter;
        $result = 1;

        $callback = function() use (&$result) { $result++; };

        $emitter->bindOnce('event.test', $callback);
        $emitter->trigger('event.test');
        $emitter->trigger('event.test');
        $emitter->trigger('event.test');

        $this->assertEquals(2, $result);
    }

    public function testUnbind()
    {
        $emitter = new Emitter;
        $result = false;

        $callback = function() use (&$result) { $result = true; };

        $emitter->bind('event.test', $callback);
        $emitter->unbind('event.test');
        $emitter->trigger('event.test');

        $this->assertEquals(false, $result);
    }

    public function testTrigger()
    {
        $emitter = new Emitter;
        $count = 0;

        $callback = function() use (&$count) { $count++; };

        $emitter->bind('event.test', $callback);
        $emitter->bind('event.test', $callback);
        $emitter->bind('event.test', $callback);
        $emitter->trigger('event.test');

        $this->assertEquals(3, $count);
    }

    public function testTriggerResult()
    {
        $emitter = new Emitter;
        $result = $emitter->trigger('event.test');
        $this->assertNull($result);

        $emitter->bind('event.test', function(){ return 'foo'; });
        $result = $emitter->trigger('event.test');
        $this->assertNotNull($result);
    }

}