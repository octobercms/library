<?php namespace October\Rain\Cron;

use Closure;
use Illuminate\Container\Container;
use October\Rain\Cron\Models\Job;
use Illuminate\Queue\Jobs\Job as JobBase;

class CronJob extends JobBase
{

    /**
     * The class name of the job.
     *
     * @var string
     */
    protected $job;

    /**
     * The queue message data.
     *
     * @var string
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $job
     * @param  string  $data
     * @return void
     */
    public function __construct(Container $container, $job)
    {
        $this->job = $job;
        $this->data = $job->payload;
        $this->container = $container;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $data = json_decode($this->data, true);

        if ($delay = $this->job->delay) {
            $this->job->setStatus(Job::STATUS_WAITING);
            sleep($delay);
        }

        $this->job->setStatus(Job::STATUS_STARTED);

        $this->resolveAndFire($data);

        if (!$this->deleted) {
            $this->job->setStatus(Job::STATUS_FINISHED);
        }
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        //
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();
        $this->job->delete();
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        //
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return '';
    }

}
