<?php namespace October\Rain\Html;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * HtmlServiceProvider
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class HtmlServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
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
        $this->app->singleton('html', function ($app) {
            return new HtmlBuilder($app['url']);
        });
    }

    /**
     * Register the form builder instance.
     * @return void
     */
    protected function registerFormBuilder()
    {
        $this->app->singleton('form', function ($app) {
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
        $this->app->singleton('block', function ($app) {
            return new BlockBuilder;
        });
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['html', 'form', 'block'];
    }
}
