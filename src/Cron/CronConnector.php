<?php namespace October\Rain\Cron;

use Illuminate\Queue\Connectors\ConnectorInterface;

class CronConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        return new CronQueue;
    }

}