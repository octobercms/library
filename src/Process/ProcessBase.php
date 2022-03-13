<?php namespace October\Rain\Process;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * ProcessBase is a base class for all other process classes
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class ProcessBase
{
    /**
     * @var string output stores the resulting output as a string
     */
    protected $output;

    /**
     * @var int exitCode stores the previous error code
     */
    protected $exitCode;

    /**
     * @var string basePath stores the directory where the process is called
     */
    protected $basePath;

    /**
     * @var callable useCallback
     */
    protected $useCallback;

    /**
     * __construct
     */
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?: base_path();
    }

    /**
     * run executes the process with the current configuration
     */
    public function run(array $command)
    {
        if ($this->useCallback !== null) {
            return $this->runCallback($command, $this->useCallback);
        }

        return $this->runNow($command);
    }

    /**
     * runNow executes the process and captures completed output
     */
    public function runNow(array $command)
    {
        $this->output = '';

        $this->runProcessQuery($command);

        return $this->output;
    }

    /**
     * runCallback executes the process with streamed output
     */
    public function runCallback(array $command, callable $callback)
    {
        $this->output = '';

        $this->runProcessQuery($command, function($type, $data) use ($callback) {
            $callback($data);
        });

        return $this->output;
    }

    /**
     * runProcessQuery is an internal helper for running processes
     */
    protected function runProcessQuery($command, $callback = null)
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->mustRun($callback);

        $this->output = $process->getOutput();
        $this->exitCode = $process->getExitCode();

        return $process;
    }

    /**
     * getPhpBinary
     */
    public function getPhpBinary(): string
    {
        return (string) (new PhpExecutableFinder)->find();
    }

    /**
     * setCallback instructs commands to execute output as a callback
     */
    public function setCallback($callback)
    {
        $this->useCallback = $callback;
    }

    /**
     * lastExitCode returns the last known exit code
     */
    public function lastExitCode()
    {
        return $this->exitCode;
    }

    /**
     * lastOutput returns the last known output
     */
    public function lastOutput()
    {
        return $this->output;
    }
}
