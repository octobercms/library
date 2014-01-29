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
    public function bind($event, $callback)
    {
        $this->emitterEventCollection[$event][] = $callback;
        return $this;
    }

    /**
     * Create a new event binding to be fired once only.
     * @return Self
     */
    public function bindOnce($event, $callback)
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
        /*
         * Multiple events
         */
        if (is_array($event)) {
            foreach ($event as $_event) {
                $this->unbind($_event);
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
     * Emits a registered event.
     *
     * If all results are NULL, then NULL is returned.
     * If one or more results is FALSE and remaining are NULL/TRUE, then FALSE is returned.
     * If one or more results is TRUE and remaining are NULL, then TRUE is returned.
     * Otherwise an array of results is returned for each event.
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
                if (($_result = call_user_func_array($callback, $params)) !== null)
                    $result[] = $_result;
            }

            unset($this->emitterSingleEventCollection[$event]);
        }

        /*
         * Recurring events
         */
        if (isset($this->emitterEventCollection[$event])) {
            foreach ($this->emitterEventCollection[$event] as $callback) {
                if (($_result = call_user_func_array($callback, $params)) !== null)
                    $result[] = $_result;
            }
        }

        /*
         * Tally up results
         */
        $falseCount = $trueCount = $returnCount = 0;
        foreach ($result as $_result) {
            if (is_bool($_result) && $_result === false)
                $falseCount++;
            elseif (is_bool($_result) && $_result === true)
                $trueCount++;
            else
                $returnCount++;
        }

        /*
         * If a non-null, non-boolean result is found, return the whole collection
         * Otherwise return false, then true, then null respectively.
         */
        if ($returnCount > 0)
            return $result;
        elseif ($falseCount > 0)
            return false;
        elseif ($trueCount > 0)
            return true;
        else
            return null;
    }
}
