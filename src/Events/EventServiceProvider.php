<?php namespace October\Rain\Events;

use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\ServiceProvider;

/**
 * EventServiceProvider
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * register the service provider
     */
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });

        $this->app->singleton('events.priority', function ($app) {
            return (new PriorityDispatcher($app))->setLaravelDispatcher($app['events']);
        });
    }
}
