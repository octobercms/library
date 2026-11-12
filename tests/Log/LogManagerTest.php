<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use October\Rain\Log\LogManager;

/**
 * LogManagerTest
 */
class LogManagerTest extends TestCase
{
    /**
     * testRebuiltChannelKeepsItsChannelContext confirms a channel built again is tagged again
     */
    public function testRebuiltChannelKeepsItsChannelContext()
    {
        $previous = Container::getInstance();
        $app = new October\Rain\Foundation\Application(sys_get_temp_dir());
        Container::setInstance($previous);
        $app->instance('events', $events = new Dispatcher($app));
        $app->instance('config', new Illuminate\Config\Repository(['logging' => ['channels' => []]]));

        $contexts = [];
        $events->listen(MessageLogged::class, function ($event) use (&$contexts) {
            $contexts[] = $event->context[LogManager::CHANNEL_CONTEXT_KEY] ?? null;
        });

        $manager = new LogManager($app);
        $config = ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class];

        $manager->build($config)->info('first');
        $manager->build($config)->info('second');

        $this->assertSame(['ondemand', 'ondemand'], $contexts);
    }
}
