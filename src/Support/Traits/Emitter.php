<?php namespace October\Rain\Support\Traits;

use Event;

/**
 * Adds event related features to any class.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
trait Emitter
{
    /**
     * Prefix to remove for local events
     * If empty will default to the first section of event key: 'component.action' global
     * would equal 'action' local event using 'component' as the event prefix
     * @var string
     * /
     * const EVENT_PREFIX = '';
     */

    /**
     * @var array Collection of registered events to be fired once only.
     */
    protected $emitterSingleEventCollection = [];

    /**
     * @var array Collection of registered events.
     */
    protected $emitterEventCollection = [];

    /**
     * @var array Sorted collection of events.
     */
    protected $emitterEventSorted = [];

    /**
     * Create a new event binding.
     * @return self
     */
    public function bindEvent($event, $callback, $priority = 0)
    {
        $this->emitterEventCollection[$event][$priority][] = $callback;
        unset($this->emitterEventSorted[$event]);
        return $this;
    }

    /**
     * Create a new event binding that fires once only
     * @return self
     */
    public function bindEventOnce($event, $callback)
    {
        $this->emitterSingleEventCollection[$event][] = $callback;
        return $this;
    }

    /**
     * Sort the listeners for a given event by priority.
     *
     * @param  string  $eventName
     * @return array
     */
    protected function emitterEventSortEvents($eventName)
    {
        $this->emitterEventSorted[$eventName] = [];

        if (isset($this->emitterEventCollection[$eventName])) {
            krsort($this->emitterEventCollection[$eventName]);

            $this->emitterEventSorted[$eventName] = call_user_func_array('array_merge', $this->emitterEventCollection[$eventName]);
        }
    }

    /**
     * Destroys an event binding.
     * @param string $event Event to destroy
     * @return self
     */
    public function unbindEvent($event = null)
    {
        /*
         * Multiple events
         */
        if (is_array($event)) {
            foreach ($event as $_event) {
                $this->unbindEvent($_event);
            }
            return;
        }

        if ($event === null) {
            unset($this->emitterSingleEventCollection);
            unset($this->emitterEventCollection);
            unset($this->emitterEventSorted);
            return $this;
        }

        if (isset($this->emitterSingleEventCollection[$event]))
            unset($this->emitterSingleEventCollection[$event]);

        if (isset($this->emitterEventCollection[$event]))
            unset($this->emitterEventCollection[$event]);

        if (isset($this->emitterEventSorted[$event]))
            unset($this->emitterEventSorted[$event]);

        return $this;
    }

    /**
     * Fire an event and call the listeners.
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     * @return array Collection of event results / Or single result (if halted)
     */
    public function fireEvent($event, $params = [], $halt = false)
    {
        if (!is_array($params)) $params = [$params];
        $result = [];

        /*
         * Single events
         */
        if (isset($this->emitterSingleEventCollection[$event])) {
            foreach ($this->emitterSingleEventCollection[$event] as $callback) {
                $response = call_user_func_array($callback, $params);
                if (is_null($response)) continue;
                if ($halt) return $response;
                $result[] = $response;
            }

            unset($this->emitterSingleEventCollection[$event]);
        }

        /*
         * Recurring events, with priority
         */
        if (isset($this->emitterEventCollection[$event])) {

            if (!isset($this->emitterEventSorted[$event]))
                $this->emitterEventSortEvents($event);

            foreach ($this->emitterEventSorted[$event] as $callback) {
                $response = call_user_func_array($callback, $params);
                if (is_null($response)) continue;
                if ($halt) return $response;
                $result[] = $response;
            }

        }

        return $halt ? null : $result;
    }

    /**
     * Fires a combination of local and global events. The first segment is removed
     * from the event name locally and the local object is passed as the first
     * argument to the event globally. Halting is also enabled by default.
     *
     * For example:
     *
     *   $this->fireCombinedEvent('backend.list.myEvent', ['my value'], true, true);
     *
     * Is equivalent to:
     *
     *   $this->fireEvent('list.myEvent', ['myvalue'], true);
     *
     *   Event::fire('backend.list.myEvent', [$this, 'myvalue'], true);
     *
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result. Default true.
     * @param boolean $prefixed The passed event has already been prefixed, remove it for the local event. Otherwise add it for the global event. Default false.
     * @return mixed
     */
    public function fireCombinedEvent($event, $params = [], $halt = true, $prefixed = false)
    {
        $result = [];
        $prefix = $this->getEventPrefix();

        $shortEvent = $prefixed ? substr($event, strpos($event, $this->getEventPrefix()) + 1) : $event;
        $event = $prefixed ? $event : $prefix . $event;
        $longArgs = array_merge([$this], $params);

        /*
         * Local event first
         */
        if ($response = $this->fireEvent($shortEvent, $params, $halt)) {
            if ($halt) {
                return $response;
            }
            else {
                $result = array_merge($result, $response);
            }
        }
        /*
         * Global event second
         */
        if ($response = Event::fire($event, $longArgs, $halt)) {
            if ($halt) {
                return $response;
            }
            else {
                $result = array_merge($result, $response);
            }
        }
        return $result;
    }

    /**
     * Gets the event prefix for this class, the prefix should end with a single period (.)
     *
     * @return string $prefix
     */
    public function getEventPrefix()
    {
        return defined('static::EVENT_PREFIX') ? static::EVENT_PREFIX . '.' : '.';
    }
}
