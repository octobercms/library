<?php namespace October\Rain\Filesystem;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Filesystem\FilesystemServiceProvider as FilesystemServiceProviderBase;

/**
 * FilesystemServiceProvider
 */
class FilesystemServiceProvider extends FilesystemServiceProviderBase
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->registerNativeFilesystem();

        $this->registerFlysystem();

        // After registration
        $this->app->booting(function () {
            $this->configureDefaultPermissions($this->app['config'], $this->app['files']);
        });
    }

    /**
     * registerNativeFilesystem implementation.
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            $config = $this->app['config'];

            $files = new Filesystem;
            $files->filePermissions = $config->get('system.default_mask.file', null);
            $files->folderPermissions = $config->get('system.default_mask.folder', null);
            $files->pathSymbols = [
                '~' => base_path()
            ];

            if ($this->app->has('path.themes')) {
                $files->pathSymbols['#'] = themes_path();
            }

            if ($this->app->has('path.plugins')) {
                $files->pathSymbols['$'] = plugins_path();
            }

            return $files;
        });
    }

    /**
     * configureDefaultPermissions
     */
    protected function configureDefaultPermissions($config, $files)
    {
        if ($config->get('filesystems.disks.local.permissions.file.public', null) === null) {
            $config->set('filesystems.disks.local.permissions.file.public', $files->getFilePermissions());
        }

        if ($config->get('filesystems.disks.local.permissions.dir.public', null) === null) {
            $config->set('filesystems.disks.local.permissions.dir.public', $files->getFolderPermissions());
        }
    }
}
