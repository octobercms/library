<?php namespace October\Rain\Process;

/**
 * Composer handles the composer process and its associated functions
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class Composer extends ProcessBase
{
    /**
     * @var bool useLocalLibrary
     */
    protected $useLocalLibrary;

    /**
     * useLocalLibrary tells composer to use the local library version to run
     * commands, this is useful when composer is not installed on the server
     */
    public function useLocalLibrary(bool $value = true)
    {
        $this->useLocalLibrary = $value;
    }

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
    public function require($package, $version = null, ...$args)
    {
        $args[] = '--update-with-dependencies';

        if ($version) {
            $this->runComposerCommand('require', $package, $version, ...$args);
        }
        else {
            $this->runComposerCommand('require', $package, ...$args);
        }
    }

    /**
     * requireNoUpdate will include a package without dependencies
     */
    public function requireNoUpdate($package, $version = null, ...$args)
    {
        return $this->require($package, $version, '--no-update', ...$args);
    }

    /**
     * remove runs the "composer remove" command
     */
    public function remove($package, ...$args)
    {
        $this->runComposerCommand('remove', $package, ...$args);
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
     * listPackages returns a list of directly installed packages
     */
    public function listPackages()
    {
        return $this->listPackagesInternal();
    }

    /**
     * listAllPackages returns a list of installed packages, including dependencies
     */
    public function listAllPackages()
    {
        return $this->listPackagesInternal(false);
    }

    /**
     * listPackagesInternal returns a list of installed packages
     */
    protected function listPackagesInternal($useDirect = true)
    {
        $command = ['show', '--format=json'];

        if ($useDirect) {
            $command[] = '--direct';
        }

        $installed = json_decode($this->runComposerCommand(...$command), true);

        $packages = [];

        foreach (array_get($installed, 'installed', []) as $package) {
            $package['version'] = ltrim($package['version'] ?? '', 'v');
            $packages[] = $package;
        }

        return $packages;
    }

    /**
     * runComposerCommand is a helper for running a git command
     */
    protected function runComposerCommand(...$parts)
    {
        return $this->run($this->prepareComposerCommand($parts));
    }

    /**
     * prepareComposerCommand is a helper for preparing arguments
     */
    protected function prepareComposerCommand($parts)
    {
        if ($this->useLocalLibrary) {
            return array_merge([
                $this->getPhpBinary(),
                'vendor/composer/composer/bin/composer'
            ], $parts);
        }

        return array_merge([
            $this->getComposerBin()
        ], $parts);
    }

    /**
     * getComposerBin
     */
    protected function getComposerBin(): string
    {
        return (string) env('COMPOSER_BIN', 'composer');
    }
}
