<?php namespace October\Rain\Cron;

use October\Rain\Cron\Models\Job;
use Illuminate\Queue\QueueInterface;
use Illuminate\Queue\Queue as QueueBase;

class CronQueue extends QueueBase implements QueueInterface
{

    /**
     * Push a new job onto the queue.
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->createJob($job, $data);
        return 0;
    }

    /**
     * Push a new job onto the queue after a delay.
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $delay = $this->getSeconds($delay);
        $this->createJob($job, $data, $delay);
        return 0;
    }

    /**
     * Record the job instance in the database.
     * @param  string  $jobItem
     * @param  array   $data
     * @param  integer $delay
     * @return Model
     */
    public function createJob($jobItem, $data, $delay = 0)
    {
        $job = new Job;
        $job->status = Job::STATUS_OPEN;
        $job->delay = $delay;
        $job->payload = $this->createPayload($jobItem, $data);
        $job->save();

        return $job;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        //
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Queue\Jobs\Job|null
     */
    public function pop($queue = null) {}

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $job
     * @param  string  $data
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($job, $data)
    {
        return new Jobs\SyncJob($this->container, $job, $data);
    }

}
