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
     * Create a new event binding.
     * @return Self
     */
    public function bindEvent($event, $callback, $onceOnly = false)
    {
        if ($onceOnly)
            $this->emitterSingleEventCollection[$event][] = $callback;
        else
            $this->emitterEventCollection[$event][] = $callback;

        return $this;
    }

    /**
     * Destroys an event binding.
     * @param string $event Event to destroy
     * @return Self
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
            return $this;
        }

        if (isset($this->emitterSingleEventCollection[$event]))
            unset($this->emitterSingleEventCollection[$event]);

        if (isset($this->emitterEventCollection[$event]))
            unset($this->emitterEventCollection[$event]);

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
         * Recurring events
         */
        if (isset($this->emitterEventCollection[$event])) {
            foreach ($this->emitterEventCollection[$event] as $callback) {
                $response = call_user_func_array($callback, $params);
                if (is_null($response)) continue;
                if ($halt) return $response;
                $result[] = $response;
            }
        }

        return $halt ? null : $result;
    }
}
