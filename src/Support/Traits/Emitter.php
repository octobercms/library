<?php namespace October\Rain\Support\Traits;

/**
 * Adds event related features to a class.
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
    public function bind($event, callable $callback)
    {
        $this->emitterEventCollection[$event][] = $callback;
        return $this;
    }

    /**
     * Create a new event binding to be fired once only.
     * @return Self
     */
    public function bindOnce($event, callable $callback)
    {
        $this->emitterSingleEventCollection[$event][] = $callback;
        return $this;
    }

    /**
     * Destroys an event binding.
     * @param string $event Event to destroy
     * @return Self
     */
    public function unbind($event = null)
    {
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
     * Emits a registered event.
     * @param string $event Event name
     * @return array Collection of event results
     */
    public function trigger($event)
    {
        $params = array_slice(func_get_args(), 1);
        $result = [];

        /*
         * Single events
         */
        if (isset($this->emitterSingleEventCollection[$event])) {
            foreach ($this->emitterSingleEventCollection[$event] as $callback) {
                if ($_result = call_user_func_array($callback, $params))
                    $result[] = $_result;
            }

            unset($this->emitterSingleEventCollection);
        }

        /*
         * Recurring events
         */
        if (isset($this->emitterEventCollection[$event])) {
            foreach ($this->emitterEventCollection[$event] as $callback) {
                if ($_result = call_user_func_array($callback, $params))
                    $result[] = $_result;
            }
        }

        return $result ?: null;
    }
}
