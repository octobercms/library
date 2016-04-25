<?php namespace October\Rain\Support\Traits;

/**
 * Adds event related features to any class.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
trait Emitter
{
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
}
