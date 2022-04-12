<?php namespace October\Rain\Support\Traits;

/**
 * Emitter adds event related features to any class
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
trait Emitter
{
    /**
     * @var array emitterSingleEventCollection of events to be fired once only
     */
    protected $emitterSingleEventCollection = [];

    /**
     * @var array emitterEventCollection of all registered events
     */
    protected $emitterEventCollection = [];

    /**
     * @var array emitterEventSorted collection
     */
    protected $emitterEventSorted = [];

    /**
     * bindEvent creates a new event binding
     * @return void
     */
    public function bindEvent($event, $callback, $priority = 0)
    {
        $this->emitterEventCollection[$event][$priority][] = $callback;
        unset($this->emitterEventSorted[$event]);
    }

    /**
     * bindEventOnce creates a new event binding that fires once only
     * @return void
     */
    public function bindEventOnce($event, $callback, $priority = 0)
    {
        $this->emitterSingleEventCollection[$event][$priority][] = $callback;
        unset($this->emitterEventSorted[$event]);
    }

    /**
     * unbindEvent destroys an event binding
     * @return void
     */
    public function unbindEvent($event = null)
    {
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
            return;
        }

        unset($this->emitterSingleEventCollection[$event]);
        unset($this->emitterEventCollection[$event]);
        unset($this->emitterEventSorted[$event]);
    }

    /**
     * fireEvent and call the listeners
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     * @return array Collection of event results / Or single result (if halted)
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
            return $halt ? null : [];
        }

        if (!isset($this->emitterEventSorted[$event])) {
            $this->emitterEventSorted[$event] = $this->emitterEventSortEvents($event);
        }

        $result = [];
        foreach ($this->emitterEventSorted[$event] as $callback) {
            $response = $callback(...$params);

            if (!is_null($response) && $halt) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            if (!is_null($response)) {
                $result[] = $response;
            }
        }

        if (isset($this->emitterSingleEventCollection[$event])) {
            unset($this->emitterSingleEventCollection[$event]);
            unset($this->emitterEventSorted[$event]);
        }

        return $halt ? null : $result;
    }

    /**
     * emitterEventSortEvents sorts the listeners for a given event by priority
     */
    protected function emitterEventSortEvents(string $eventName, array $combined = []): array
    {
        if (isset($this->emitterEventCollection[$eventName])) {
            foreach ($this->emitterEventCollection[$eventName] as $priority => $callbacks) {
                $combined[$priority] = array_merge($combined[$priority] ?? [], $callbacks);
            }
        }

        if (isset($this->emitterSingleEventCollection[$eventName])) {
            foreach ($this->emitterSingleEventCollection[$eventName] as $priority => $callbacks) {
                $combined[$priority] = array_merge($combined[$priority] ?? [], $callbacks);
            }
        }

        krsort($combined);

        return call_user_func_array('array_merge', $combined);
    }
}
