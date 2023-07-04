<?php namespace October\Rain\Events;

use Str;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Container\Container as ContainerContract;

/**
 * PriorityDispatcher is a global event emitter with priority assignment.
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
class PriorityDispatcher
{
    use \October\Rain\Support\Traits\Emitter;
    use \Illuminate\Support\Traits\ForwardsCalls;

    const FORWARD_CALL_FLAG = '___FORWARD_CALL___';

    /**
     * @var \Illuminate\Contracts\Container\Container container for IoC
     */
    protected $container;

    /**
     * @var DispatcherContract laravelEvents instance.
     */
    protected $laravelEvents;

    /**
     * __construct a new event dispatcher instance.
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container ?: new Container;
    }

    /**
     * listen registers an event listener with the dispatcher.
     * @param string|array $events
     * @param mixed|null $listener
     * @param int $priority
     * @return void
     */
    public function listen($events, $listener = null, $priority = 0)
    {
        if ($priority === 0) {
            $this->laravelEvents->listen($events, $listener);
        }
        else {
            $this->bindEvent($events, $listener, $priority);
        }
    }

    /**
     * listenOnce registers an event that only fires once.
     * @param string|array $events
     * @param callable $listener
     * @param int $priority
     * @return void
     */
    public function listenOnce($events, $listener, $priority = 0)
    {
        $this->bindEventOnce($events, $listener, $priority);
    }

    /**
     * fire an event and call the listeners.
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->fireEvent($event, $payload, $halt);
    }

    /**
     * forget removes a set of listeners from the dispatcher.
     * @param  string  $event
     * @return void
     */
    public function forget($event)
    {
        $this->unbindEvent($event);

        $this->laravelEvents->forget($event);
    }

    /**
     * fireEvent inherits logic from the Emitter, modified to forward call to Laravel events
     * @param string $event
     * @param array $params
     * @param boolean $halt
     * @return array
     */
    public function fireEvent($event, $params = [], $halt = false)
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        // Micro optimization
        if (
            !isset($this->emitterEventCollection[$event]) &&
            !isset($this->emitterSingleEventCollection[$event])
        ) {
            return $this->laravelEvents->dispatch($event, $params, $halt);
        }

        if (!isset($this->emitterEventSorted[$event])) {
            $this->emitterEventSorted[$event] = $this->emitterEventSortEvents($event, [
                0 => [self::FORWARD_CALL_FLAG]
            ]);
        }

        $result = [];
        foreach ($this->emitterEventSorted[$event] as $callback) {
            if ($callback === self::FORWARD_CALL_FLAG) {
                $response = $this->laravelEvents->dispatch($event, $params, $halt);
                $isLaravel = true;
            }
            else {
                if (is_string($callback)) {
                    $callback = $this->createClassCallback($callback);
                }

                if (is_array($callback) && isset($callback[0]) && is_string($callback[0])) {
                    $callback = $this->createClassCallback($callback);
                }

                $response = $callback(...$params);
                $isLaravel = false;
            }

            if (!is_null($response) && $halt) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            if (!is_null($response)) {
                if ($isLaravel) {
                    $result = array_merge($result, $response);
                }
                else {
                    $result[] = $response;
                }
            }
        }

        if (isset($this->emitterSingleEventCollection[$event])) {
            unset($this->emitterSingleEventCollection[$event]);
            unset($this->emitterEventSorted[$event]);
        }

        return $halt ? null : $result;
    }

    /**
     * setLaravelDispatcher sets the event resolver implementation.
     */
    public function setLaravelDispatcher(DispatcherContract $dispatcher): PriorityDispatcher
    {
        $this->laravelEvents = $dispatcher;

        return $this;
    }

    /**
     * getLaravelDispatcher returns the base event resolver.
     */
    public function getLaravelDispatcher(): DispatcherContract
    {
        return $this->laravelEvents;
    }

    /**
     * createClassCallback passes what is usually a static method call through the IoC
     * container to create a callable instance.
     */
    protected function createClassCallback($callback)
    {
        if (is_callable($callback)) {
            return $callback;
        }

        [$class, $method] = is_array($callback)
            ? $callback
            : Str::parseCallback($callback, 'handle');

        if (!method_exists($class, $method)) {
            $method = '__invoke';
        }

        $listener = $this->container->make($class);

        return [$listener, $method];
    }

    /**
     * __call magic
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->laravelEvents,
            $method,
            $parameters
        );
    }
}
