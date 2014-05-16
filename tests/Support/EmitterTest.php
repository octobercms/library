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

        $emitter->fireEvent('event.test');
        $this->assertEquals(false, $result);

        $emitter->bindEvent('event.test', function() use (&$result) {
            $result = true;
        });

        $emitter->fireEvent('event.test');
        $this->assertEquals(true, $result);
    }

    public function testBindOnce()
    {
        $emitter = $this->traitObject;
        $result = 1;

        $callback = function() use (&$result) { $result++; };

        $emitter->bindEvent('event.test', $callback, true);
        $emitter->fireEvent('event.test');
        $emitter->fireEvent('event.test');
        $emitter->fireEvent('event.test');

        $this->assertEquals(2, $result);
    }

    public function testUnbindEvent()
    {
        $emitter = $this->traitObject;
        $result = false;

        $callback = function() use (&$result) { $result = true; };

        $emitter->bindEvent('event.test', $callback);
        $emitter->unbindEvent('event.test');
        $emitter->fireEvent('event.test');

        $this->assertEquals(false, $result);
    }

    public function testFireEvent()
    {
        $emitter = $this->traitObject;
        $count = 0;

        $callback = function() use (&$count) { $count++; };

        $emitter->bindEvent('event.test', $callback);
        $emitter->bindEvent('event.test', $callback);
        $emitter->bindEvent('event.test', $callback);
        $emitter->fireEvent('event.test');

        $this->assertEquals(3, $count);
    }

    public function testFireEventResult()
    {
        $emitter = $this->traitObject;
        $result = $emitter->fireEvent('event.test');
        $this->assertEmpty($result);

        $emitter->bindEvent('event.test', function(){ return 'foo'; });
        $result = $emitter->fireEvent('event.test');
        $this->assertNotNull($result);
    }

}