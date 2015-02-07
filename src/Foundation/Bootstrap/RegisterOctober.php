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
    }

}