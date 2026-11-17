<?php

use Illuminate\Container\Container;
use October\Rain\Events\Dispatcher;
use October\Rain\Events\PriorityDispatcher;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class EventBench
{
    /**
     * @var PriorityDispatcher dispatcher
     */
    protected $dispatcher;

    /**
     * init
     */
    public function init()
    {
        $app = new Container;
        $this->dispatcher = (new PriorityDispatcher($app))->setLaravelDispatcher(new Dispatcher($app));

        for ($i = 0; $i < 10; $i++) {
            $this->dispatcher->listen('bench.closures', function ($value) {
            });
            $this->dispatcher->listen('bench.classes', EventBenchListener::class.'@handle');
            $this->dispatcher->listen('bench.priorityClosures', function ($value) {
            }, 10);
            $this->dispatcher->listen('bench.priorityClasses', EventBenchListener::class.'@handle', 10);
        }

        EventBenchListener::$calls = 0;
        $this->dispatcher->fire('bench.classes', ['value']);
        $this->dispatcher->fire('bench.priorityClasses', ['value']);
        if (EventBenchListener::$calls !== 20) {
            throw new RuntimeException('Event fixture mismatch');
        }
    }

    /**
     * @Subject
     */
    public function benchFireWithoutListeners()
    {
        $this->dispatcher->fire('bench.none', ['value']);
    }

    /**
     * @Subject
     */
    public function benchFireClosures()
    {
        $this->dispatcher->fire('bench.closures', ['value']);
    }

    /**
     * @Subject
     */
    public function benchFireClassListeners()
    {
        $this->dispatcher->fire('bench.classes', ['value']);
    }

    /**
     * @Subject
     */
    public function benchFirePriorityClosures()
    {
        $this->dispatcher->fire('bench.priorityClosures', ['value']);
    }

    /**
     * @Subject
     */
    public function benchFirePriorityClassListeners()
    {
        $this->dispatcher->fire('bench.priorityClasses', ['value']);
    }
}

class EventBenchListener
{
    public static $calls = 0;

    public function handle($value)
    {
        static::$calls++;
    }
}
