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
         * Define path constants
         */
        if (!defined('PATH_APP')) {
            define('PATH_APP', app_path());
        }
        if (!defined('PATH_BASE')) {
            define('PATH_BASE', base_path());
        }
        if (!defined('PATH_PUBLIC')) {
            define('PATH_PUBLIC', public_path());
        }
        if (!defined('PATH_STORAGE')) {
            define('PATH_STORAGE', storage_path());
        }
        if (!defined('PATH_PLUGINS')) {
            define('PATH_PLUGINS', base_path() . $app['config']->get('cms.pluginsDir', '/plugins'));
        }

        /*
         * Register singletons
         */
        App::singleton('string', function () {
            return new \October\Rain\Support\Str;
        });
    }

}