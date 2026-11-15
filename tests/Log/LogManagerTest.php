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
     * @var array loggedContexts holds the context of each logged message
     */
    protected $loggedContexts = [];

    /**
     * testRebuiltChannelKeepsItsChannelContext confirms a channel built again is tagged again
     */
    public function testRebuiltChannelKeepsItsChannelContext()
    {
        $manager = $this->makeLogManager();
        $config = ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class];

        $manager->build($config)->info('first');
        $manager->build($config)->info('second');

        $this->assertSame(['ondemand', 'ondemand'], $this->getLoggedChannels());
    }

    /**
     * testForgottenChannelKeepsItsChannelContext confirms a channel resolved after forgetChannel is tagged again
     */
    public function testForgottenChannelKeepsItsChannelContext()
    {
        $manager = $this->makeLogManager();

        $manager->channel('test')->info('first');
        $manager->forgetChannel('test');
        $manager->channel('test')->info('second');

        $this->assertSame(['test', 'test'], $this->getLoggedChannels());
    }

    /**
     * testClearedContextKeepsTheChannelContext confirms withoutContext, as run by the queue worker, keeps the channel tag
     */
    public function testClearedContextKeepsTheChannelContext()
    {
        $manager = $this->makeLogManager();

        $logger = $manager->channel('test');
        $logger->withContext(['user' => 'Jeff']);

        $manager->withoutContext();

        $logger->info('held');
        $manager->channel('test')->info('resolved');

        $this->assertSame(['test', 'test'], $this->getLoggedChannels());
        $this->assertArrayNotHasKey('user', $this->loggedContexts[0]);
    }

    /**
     * makeLogManager with an isolated application and a "test" channel
     */
    protected function makeLogManager(): LogManager
    {
        $previous = Container::getInstance();
        $app = new October\Rain\Foundation\Application(sys_get_temp_dir());
        Container::setInstance($previous);
        $app->instance('events', $events = new Dispatcher($app));
        $app->instance('config', new Illuminate\Config\Repository(['logging' => ['channels' => [
            'test' => ['driver' => 'monolog', 'handler' => Monolog\Handler\NullHandler::class],
        ]]]));

        $this->loggedContexts = [];
        $events->listen(MessageLogged::class, function ($event) {
            $this->loggedContexts[] = $event->context;
        });

        return new LogManager($app);
    }

    /**
     * getLoggedChannels returns the channel tag of each logged message
     */
    protected function getLoggedChannels(): array
    {
        return array_map(fn ($context) => $context[LogManager::CHANNEL_CONTEXT_KEY] ?? null, $this->loggedContexts);
    }
}
