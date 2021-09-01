<?php namespace October\Rain\Filesystem;

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
                '#' => themes_path(),
                '$' => plugins_path(),
                '~' => base_path(),
            ];
            return $files;
        });
    }

    /**
     * provides gets the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['files', 'filesystem'];
    }
}
