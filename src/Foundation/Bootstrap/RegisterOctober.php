<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Http\Request;
use October\Rain\Support\ClassLoader;
use Illuminate\Contracts\Foundation\Application;

class RegisterOctober
{

    /**
     * Specific features for OctoberCMS.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        /*
         * Register singletons
         */
        $app->singleton('string', function () {
            return new \October\Rain\Support\Str;
        });

        /*
         * Change paths based on config
         */
        if ($pluginsPath = $app['config']->get('cms.pluginsDir')) {
            $app->setPluginsPath(base_path().$pluginsPath);
        }

        if ($themesPath = $app['config']->get('cms.themesDir')) {
            $app->setThemesPath(base_path().$themesPath);
        }

        if ($uploadsPath = $app['config']->get('cms.uploadsDir')) {
            $app->setUploadsPath(base_path().$uploadsPath);
        }
    }

}