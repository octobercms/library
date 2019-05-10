<?php

use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use October\Rain\Events\Dispatcher;

class DispatcherTest extends TestCase
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function setUp()
    {
        $this->dispatcher = new Dispatcher();
    }

    private function buildQueueResolverMock(callable $pushCallback)
    {
        $queueResolver = function () use ($pushCallback) {
            $queue = $this->createMock(Queue::class);
            $queue->method('push')->will($this->returnCallback($pushCallback));
            return $queue;
        };
        return $queueResolver;
    }

    public function testCreateClassListener()
    {
        $this->dispatcher->setQueueResolver($this->buildQueueResolverMock(function ($job, $data = '', $queue = null) {
            list($class, $method) = explode('@', $job);
            $this->assertTrue(class_exists($class), sprintf('Job class %s does not exist', $class));
        }));
        $this->dispatcher->createClassListener('MockEventListenerCallable@handle')();
        $this->dispatcher->createClassListener('MockEventListenerQueueMethod@handle')();
    }
}

class MockEventListenerCallable implements ShouldQueue
{
    public function handle($event)
    {
    }
}

class MockEventListenerQueueMethod implements ShouldQueue
{
    public function handle($event)
    {
    }

    public function queue($queue, $job, $data)
    {
        $queue->push($job, $data);
    }
}
