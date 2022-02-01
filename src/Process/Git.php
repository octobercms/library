<?php namespace October\Rain\Process;

/**
 * Git handles the git process and its associated functions
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class Git extends ProcessBase
{
    /**
     * commit staged files in git
     */
    public function commit($message = '')
    {
        return $this->runGitCommand('commit', '-a', "-m {$message}");
    }

    /**
     * push pushes staged files in git
     */
    public function push()
    {
        return $this->runGitCommand('push');
    }

    /**
     * runGitCommand is a helper for running a git command
     */
    protected function runGitCommand(...$parts)
    {
        return $this->run($this->prepareGitCommand($parts));
    }

    /**
     * prepareGitCommand is a helper for preparing arguments
     */
    protected function prepareGitCommand($parts)
    {
        return array_merge([
            $this->getGitBin()
        ], $parts);
    }

    /**
     * getComposerBin
     */
    protected function getGitBin(): string
    {
        return (string) env('GIT_BIN', 'git');
    }
}
