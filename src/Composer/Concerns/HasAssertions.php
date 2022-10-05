<?php namespace October\Rain\Composer\Concerns;

use Composer\Util\Platform;

/**
 * HasAssertions for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasAssertions
{
    /**
     * @var string workingDir
     */
    protected $workingDir;

    /**
     * assertResourceLimits
     */
    protected function assertResourceLimits()
    {
        @set_time_limit(3600);
        ini_set('max_input_time', 0);
        ini_set('max_execution_time', 0);
    }

    /**
     * assertHomeVariableSet
     */
    protected function assertHomeVariableSet()
    {
        // Something usable is already set
        $osHome = Platform::isWindows() ? 'APPDATA' : 'HOME';
        if (getenv('COMPOSER_HOME') || getenv($osHome)) {
            return;
        }

        $tempPath = temp_path('composer');
        if (!file_exists($tempPath)) {
            @mkdir($tempPath);
        }

        putenv('COMPOSER_HOME='.$tempPath);
    }

    /**
     * assertHomeDirectory
     */
    protected function assertHomeDirectory()
    {
        $this->workingDir = getcwd();
        chdir(dirname($this->getJsonPath()));
    }

    /**
     * assertWorkingDirectory
     */
    protected function assertWorkingDirectory()
    {
        chdir($this->workingDir);
    }
}
