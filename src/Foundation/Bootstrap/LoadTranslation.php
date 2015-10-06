<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Translation\Translator;
use October\Rain\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class LoadTranslation
{

    /**
     * Bootstrap the translator.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {

        $app->singleton('translation.loader', function($app) {
            return new FileLoader($app['files'], $app['path.lang']);
        });

        $app->singleton('translator', function($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

    }

}