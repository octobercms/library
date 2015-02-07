<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Http\Request;
use October\Rain\Support\ClassLoader;
use Illuminate\Contracts\Foundation\Application;

class RegisterRequest
{

    /**
     * For compatability, the Request object should exist in the container.
     * Used by the Console Kernel.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->bindShared('request', function(){
            return Request::capture();
        });
    }

}