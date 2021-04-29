<?php namespace October\Rain\Process;

use Config;

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
        return $this->run($this->prepareGitArguments($parts));
    }

    /**
     * prepareGitArguments is a helper for preparing arguments
     */
    protected function prepareGitArguments($parts)
    {
        $gitBin = Config::get('system.git_binary', 'git');

        return implode(' ', array_merge([
            '"'.$gitBin.'"'
        ], $parts));
    }
}
