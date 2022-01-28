<?php namespace October\Rain\Events\Concerns;

use Str;

/**
 * HasListener
 *
 * @package october\events
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasListener
{
    /**
     * listen registers an event listener with the dispatcher.
     * @param string|array $events
     * @param mixed $listener
     * @param int $priority
     * @return void
     */
    protected function listenPriority($events, $listener, $priority = 0)
    {
        foreach ((array) $events as $event) {
            if (Str::contains($event, '*')) {
                $this->setupWildcardListenPriority($event, $listener);
            }
            else {
                $this->listeners[$event][$priority][] = $this->laravelEvents->makeListener($listener);

                unset($this->sorted[$event]);
            }
        }
    }

    /**
     * setupWildcardListenPriority sets up a wildcard listener callback.
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    protected function setupWildcardListenPriority($event, $listener)
    {
        $this->wildcards[$event][] = $this->laravelEvents->makeListener($listener, true);

        $this->wildcardsCache = [];
    }
}
