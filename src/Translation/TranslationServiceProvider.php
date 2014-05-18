<?php namespace October\Rain\Translation;

use Illuminate\Translation\FileLoader;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->bindShared('translator', function($app){
            return new Translator($app['translation.loader'], $app['config']['app.locale'], $app['config']['app.fallback_locale'], $app['files']);
        });
    }

    /**
     * Register the translation line loader.
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->bindShared('translation.loader', function($app){
            return new FileLoader($app['files'], $app['path'].'/lang');
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return array('translator', 'translation.loader');
    }

}
