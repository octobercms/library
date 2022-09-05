<?php namespace October\Rain\Process;

use October\Rain\Composer\Manager as ComposerManager;

/**
 * @deprecated
 * @see October\Rain\Composer\Manager
 */
class ComposerPhp extends ProcessBase
{
    /**
     * listPackages returns a list of directly installed packages
     */
    public function listPackages()
    {
        return ComposerManager::instance()->listPackages();
    }

    /**
     * listAllPackages returns a list of installed packages, including dependencies
     */
    public function listAllPackages()
    {
        return ComposerManager::instance()->listAllPackages();
    }

    /**
     * addRepository will add a repository to the composer config
     */
    public function addRepository($name, $type, $address)
    {
        return ComposerManager::instance()->addRepository($name, $type, $address);
    }
}
