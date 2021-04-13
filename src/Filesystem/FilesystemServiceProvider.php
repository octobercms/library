<?php namespace October\Rain\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider as FilesystemServiceProviderBase;

class FilesystemServiceProvider extends FilesystemServiceProviderBase
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerNativeFilesystem();

        $this->registerFlysystem();
    }

    /**
     * Register the native filesystem implementation.
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('system.default_mask.file', null);
            $files->folderPermissions = $config->get('system.default_mask.folder', null);
            $files->pathSymbols = [
                '$' => plugins_path(),
                '~' => base_path(),
            ];
            return $files;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['files', 'filesystem'];
    }
}
