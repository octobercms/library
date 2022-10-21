<?php namespace October\Rain\Composer;

use Composer\Factory as FactoryBase;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Composer\Package\RootPackageInterface;
use Composer\Util\ProcessExecutor;
use Composer\Util\Filesystem;
use Composer\Json\JsonFile;

/**
 * Factory overrides the composer Factory
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
class Factory extends FactoryBase
{
    /**
     * @param Repository\RepositoryManager $rm
     * @param string                       $vendorDir
     *
     * @return void
     */
    protected function addLocalRepository(IOInterface $io, RepositoryManager $rm, string $vendorDir, RootPackageInterface $rootPackage, ProcessExecutor $process = null): void
    {
        $fs = null;
        if ($process) {
            $fs = new Filesystem($process);
        }

        $rm->setLocalRepository(new Repository\InstalledFilesystemRepository(new JsonFile($vendorDir.'/composer/installed.json', null, $io), true, $rootPackage, $fs));
    }
}
