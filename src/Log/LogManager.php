<?php namespace October\Rain\Log;

use Illuminate\Log\LogManager as LogManagerBase;

/**
 * LogManager extends the framework LogManager so the originating channel
 * name is available to MessageLogged listeners.
 *
 * Laravel's MessageLogged event does not carry the channel name, and the
 * Logger instance itself does not store it. We tag each resolved channel
 * with withContext(['log_channel' => $name]), which merges into the
 * $context array passed to MessageLogged listeners.
 *
 * Listeners can then read $event->context['log_channel'] to filter by
 * channel.
 */
class LogManager extends LogManagerBase
{
    /**
     * CHANNEL_CONTEXT_KEY is the context array key that carries the
     * originating channel name on every log record.
     */
    public const CHANNEL_CONTEXT_KEY = 'log_channel';

    /**
     * get resolves a channel and tags it with its name via withContext().
     */
    protected function get($name, ?array $config = null)
    {
        // A channel is tagged once, when it is resolved, including after it was rebuilt or forgotten
        $isResolved = isset($this->channels[$name]);

        $logger = parent::get($name, $config);

        if (!$isResolved) {
            $logger->withContext([self::CHANNEL_CONTEXT_KEY => $name]);
        }

        return $logger;
    }
}