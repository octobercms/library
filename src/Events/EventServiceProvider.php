<?php namespace October\Rain\Events;

use October\Rain\Events\Dispatcher;
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
            // return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
            //     return $app->make(QueueFactoryContract::class);
            // })->setTransactionManagerResolver(function () use ($app) {
            //     return $app->bound('db.transactions')
            //         ? $app->make('db.transactions')
            //         : null;
            // });

            // The following adds support for Laravel 10.30 when a transaction manager resolver
            // was included as part of the dispatcher. Detect its presence and set it as needed
            // @deprecated remove reflection and use code above in v4 (Laravel 11)
            $dispatcher = (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });

            if (method_exists($dispatcher, 'setTransactionManagerResolver')) {
                $dispatcher->setTransactionManagerResolver(function () use ($app) {
                    return $app->bound('db.transactions')
                        ? $app->make('db.transactions')
                        : null;
                });
            }

            return $dispatcher;
        });

        $this->app->singleton('events.priority', function ($app) {
            return (new PriorityDispatcher($app))->setLaravelDispatcher($app['events']);
        });
    }
}
