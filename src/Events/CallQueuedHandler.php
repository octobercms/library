<?php namespace October\Rain\Events;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Container\Container;

/**
 * CallQueuedHandler
 */
class CallQueuedHandler
{
    /**
     * @var \Illuminate\Contracts\Container\Container container for the IoC instance.
     */
    protected $container;

    /**
     * __construct a new job instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * call handles the queued job.
     */
    public function call(Job $job, array $data)
    {
        $handler = $this->setJobInstanceIfNecessary(
            $job,
            $this->container->make($data['class'])
        );

        call_user_func_array(
            [$handler, $data['method']],
            unserialize($data['data'])
        );

        if (! $job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * setJobInstanceIfNecessary sets the job instance of the given class if necessary.
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array('Illuminate\Queue\InteractsWithQueue', class_uses_recursive(get_class($instance)))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * failed calls the failed method on the job instance.
     */
    public function failed(array $data)
    {
        $handler = $this->container->make($data['class']);

        if (method_exists($handler, 'failed')) {
            call_user_func_array([$handler, 'failed'], unserialize($data['data']));
        }
    }
}
