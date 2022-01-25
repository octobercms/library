<?php

namespace October\Rain\Events\Concerns;

use Arr;
use Str;

/**
 * HasTrigger
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasTrigger
{
    /**
     * dispatch fires an event and call the listeners.
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    protected function dispatchGlobally($event, $payload = [], $halt = false)
    {
        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        [$event, $payload] = $this->parseEventAndPayload($event, $payload);

        $responses = [];

        // If an array is not given to us as the payload, we will turn it into one so
        // we can easily use call_user_func_array on the listeners, passing in the
        // payload to each of them so that they receive each of these arguments.
        if (!is_array($payload)) {
            $payload = [$payload];
        }

        $this->firing[] = $event;

        foreach ($this->getGlobalListeners($event) as $listener) {
            if ($listener === '___FORWARD_CALL___') {
                $response = $this->laravelEvents->dispatch($event, $payload, $halt);
                $isLaravel = true;
            }
            else {
                $response = $listener($event, $payload);
                $isLaravel = false;
            }

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (!is_null($response) && $halt) {
                array_pop($this->firing);

                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            // If an event does not return anything the void response will be null,
            // these meaningless values should not contribute to the collection.
            if (!is_null($response)) {
                if ($isLaravel) {
                    $responses = array_merge($responses, $response);
                }
                else {
                    $responses[] = $response;
                }
            }
        }

        array_pop($this->firing);

        return $halt ? null : $responses;
    }

    /**
     * parseEventAndPayload parses the given event and payload and prepare them for dispatching.
     * @param  mixed  $event
     * @param  mixed  $payload
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }

        return [$event, Arr::wrap($payload)];
    }

    /**
     * getGlobalListeners gets all of the listeners for a given event name.
     * @param  string  $eventName
     * @return array
     */
    protected function getGlobalListeners($eventName)
    {
        if (!isset($this->sorted[$eventName])) {
            $this->sortListeners($eventName);
        }

        $listeners = $this->sorted[$eventName] ?? [];

        $listeners = array_merge(
            $listeners,
            $this->wildcardsCache[$eventName] ?? $this->getWildcardListeners($eventName)
        );

        return class_exists($eventName, false)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : $listeners;
    }

    /**
     * getWildcardListeners gets the wildcard listeners for the event.
     * @param  string  $eventName
     * @return array
     */
    protected function getWildcardListeners($eventName)
    {
        $wildcards = [];

        foreach ($this->wildcards as $key => $listeners) {
            if (Str::is($key, $eventName)) {
                $wildcards = array_merge($wildcards, $listeners);
            }
        }

        return $this->wildcardsCache[$eventName] = $wildcards;
    }

    /**
     * sortListeners for a given event by priority.
     * @param  string  $eventName
     * @return array
     */
    protected function sortListeners($eventName)
    {
        $this->listeners[$eventName][0][] = '___FORWARD_CALL___';

        krsort($this->listeners[$eventName]);

        $this->sorted[$eventName] = call_user_func_array(
            'array_merge',
            $this->listeners[$eventName]
        );
    }

    /**
     * addInterfaceListeners for the event's interfaces to the given array.
     * @param  string  $eventName
     * @param  array  $listeners
     * @return array
     */
    protected function addInterfaceListeners($eventName, array $listeners = [])
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }

        return $listeners;
    }
}
