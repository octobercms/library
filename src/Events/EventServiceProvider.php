<?php namespace October\Rain\Events;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

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

        $this->app->singleton('events.global', function ($app) {
            return (new GlobalDispatcher)->setLaravelDispatcher($app['events']);
        });
    }
}
