<?php

class EmitterTest extends TestCase
{
    /**
     * The object under test.
     *
     * @var object
     */
    private $traitObject;

    /**
     * Sets up the fixture.
     *
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        $traitName = 'October\Rain\Support\Traits\Emitter';
        $this->traitObject = $this->getObjectForTrait($traitName);
    }

    //
    // Tests
    //

    public function testBind()
    {
        $emitter = $this->traitObject;
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
        $emitter = $this->traitObject;
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
        $emitter = $this->traitObject;
        $result = false;

        $callback = function() use (&$result) { $result = true; };

        $emitter->bind('event.test', $callback);
        $emitter->unbind('event.test');
        $emitter->trigger('event.test');

        $this->assertEquals(false, $result);
    }

    public function testTrigger()
    {
        $emitter = $this->traitObject;
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
        $emitter = $this->traitObject;
        $result = $emitter->trigger('event.test');
        $this->assertNull($result);

        $emitter->bind('event.test', function(){ return 'foo'; });
        $result = $emitter->trigger('event.test');
        $this->assertNotNull($result);
    }

}