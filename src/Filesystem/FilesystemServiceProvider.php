<?php namespace October\Rain\Filesystem;

use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('files', function() {
            $files = new Filesystem;
            $files->filePermissions = $this->app['config']->get('cms.defaultMask.file', null);
            $files->folderPermissions = $this->app['config']->get('cms.defaultMask.folder', null);
            return $files;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return array('files');
    }
}