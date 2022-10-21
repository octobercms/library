<?php namespace October\Rain\Composer\Repository;

use Composer\Repository\InstalledFilesystemRepository as InstalledFilesystemRepositoryBase;
use Composer\Installer\InstallationManager;

/**
 * Factory overrides the composer Factory
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
class InstalledFilesystemRepository extends InstalledFilesystemRepositoryBase
{
    /**
     * Writes writable repository.
     */
    public function write(bool $devMode, InstallationManager $installationManager)
    {
        $wantFile = realpath(__DIR__.'/../../../../../composer/composer/src/Composer/InstalledVersions.php') . PHP_EOL;
        $count = 0;

        while (!file_exists($wantFile)) {
            usleep(rand(50000, 200000));

            if ($count++ > 10) {
                break;
            }
        }

        parent::write($devMode, $installationManager);
    }
}
