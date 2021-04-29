<?php namespace October\Rain\Process;

use Config;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Composer handles the composer process and its associated functions
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class Composer extends ProcessBase
{
    /**
     * install runs the "composer install" command
     */
    public function install()
    {
        $this->runComposerCommand('install');
    }

    /**
     * update runs the "composer update" command
     */
    public function update()
    {
        $this->runComposerCommand('update');
    }

    /**
     * require runs the "composer require" command
     */
    public function require(...$packages)
    {
        $this->runComposerCommand(...array_merge(['require'], $packages));
    }

    /**
     * remove runs the "composer remove" command
     */
    public function remove(...$packages)
    {
        $this->runComposerCommand(...array_merge(['remove'], $packages));
    }

    /**
     * addRepository will add a repository to the composer config
     */
    public function addRepository($name, $type, $address)
    {
        $this->runComposerCommand(
            'config',
            "repositories.{$name}",
            $type,
            $address
        );
    }

    /**
     * removeRepository will remove a repository to the composer config
     */
    public function removeRepository($name)
    {
        $this->runComposerCommand(
            'config',
            '--unset',
            "repositories.{$name}"
        );
    }

    /**
     * isInstalled returns true if composer is installed
     */
    public function isInstalled()
    {
        $this->runComposerCommand('--version');
        return $this->lastExitCode() === 0;
    }

    /**
     * listPackages returns a list of installed packages
     */
    public function listPackages()
    {
        $installed = json_decode($this->runComposerCommand(
            'show',
            '--direct',
            '--format=json'
        ), true);

        $packages = [];

        foreach (array_get($installed, 'installed', []) as $package) {
            $package['version'] = ltrim(array_get($package, 'version'), 'v');
            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * runComposerCommand is a helper for running a git command
     */
    protected function runComposerCommand(...$parts)
    {
        return $this->run($this->prepareComposerArguments($parts));
    }

    /**
     * prepareComposerArguments is a helper for preparing arguments
     */
    protected function prepareComposerArguments($parts)
    {
        if ($composerBin = Config::get('system.composer_binary')) {
            return implode(' ', array_merge([$composerBin], $parts));
        }

        $phpBin = (new PhpExecutableFinder)->find();

        return implode(' ', array_merge([
            '"'.$phpBin.'"',
            'vendor/composer/composer/bin/composer'
        ], $parts));
    }
}
