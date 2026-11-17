<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use October\Rain\Events\Dispatcher;
use October\Rain\Events\PriorityDispatcher;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class GeneralBench
{
    /**
     * init
     */
    public function init()
    {
        $app = new Container;
        $app->instance('events', new Dispatcher($app));
        $app->instance('events.priority', (new PriorityDispatcher($app))->setLaravelDispatcher($app['events']));
        Facade::setFacadeApplication($app);
    }

    /**
     * @Subject
     */
    public function benchA()
    {
        (new \October\Rain\Parse\Markdown)->parse('**Hello**');
    }

    /**
     * @Subject
     */
    public function benchB()
    {
        \Str::markdown('**Hello**');
    }
}
