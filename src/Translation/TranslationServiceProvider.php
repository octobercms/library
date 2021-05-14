<?php namespace October\Rain\Translation;

use Illuminate\Support\ServiceProvider;

/**
 * TranslationServiceProvider is a custom translator implemenation based on Laravel
  *
 * @package october\translation
 * @author Alexey Bobkov, Samuel Georges
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = false;

    /**
     * register the service provider.
     */
    public function register()
    {
        $this->registerLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setEventDispatcher($app['events']);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * registerLoader registers the line loader
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader($app['files'], $app['path'].'/lang');
        });
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['translator', 'translation.loader'];
    }
}
