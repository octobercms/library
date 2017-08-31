<?php namespace October\Rain\Html;

use Illuminate\Support\ServiceProvider;

class HtmlServiceProvider extends ServiceProvider
{

    /**
     * @var bool Indicates if loading of the provider is deferred.
     */
    protected $defer = true;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerHtmlBuilder();

        $this->registerFormBuilder();

        $this->registerBlockBuilder();
    }

    /**
     * Register the HTML builder instance.
     * @return void
     */
    protected function registerHtmlBuilder()
    {
        $this->app->singleton('html', function($app) {
            return new HtmlBuilder($app['url']);
        });
    }

    /**
     * Register the form builder instance.
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function($app) {
            $form = new FormBuilder($app['html'], $app['url'], $app['session.store']->token(), str_random(40));
            return $form->setSessionStore($app['session.store']);
        });
    }

    /**
     * Register the Block builder instance.
     * @return void
     */
    protected function registerBlockBuilder()
    {
        $this->app->singleton('block', function($app) {
            return new BlockBuilder;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['html', 'form', 'block'];
    }

}
