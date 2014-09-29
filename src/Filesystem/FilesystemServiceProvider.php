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
            $config = $this->app['config'];
            $files = new Filesystem;
            $files->filePermissions = $config->get('cms.defaultMask.file', null);
            $files->folderPermissions = $config->get('cms.defaultMask.folder', null);
            $files->pathSymbols = [
                '$' => base_path() . $config->get('cms.pluginsDir', '/plugins'),
                '~' => base_path(),
                '@' => base_path(), // @deprecated
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
        return array('files');
    }
}