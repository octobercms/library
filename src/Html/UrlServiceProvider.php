<?php namespace October\Rain\Html;

use Illuminate\Support\ServiceProvider;

class UrlServiceProvider extends ServiceProvider
{

    /**
     * @var bool Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->setUrlGeneratorPolicy();
    }

    /**
     * Controls how URL links are generated throughout the application.
     *
     * detect   - detect hostname and use the current schema
     * secure   - detect hostname and force HTTPS schema
     * insecure - detect hostname and force HTTP schema
     * force    - force hostname and schema using app.url config value
     */
    public function setUrlGeneratorPolicy()
    {
        $policy = $this->app['config']->get('cms.linkPolicy', 'detect');

        switch (strtolower($policy)) {
            case 'force':
                $appUrl = $this->app['config']->get('app.url');
                $schema = \Str::startsWith($appUrl, 'http://') ? 'http' : 'https';
                $this->app['url']->forceRootUrl($appUrl);
                $this->app['url']->forceScheme($schema);
                break;

            case 'insecure':
                $this->app['url']->forceScheme('http');
                break;

            case 'secure':
                $this->app['url']->forceScheme('https');
                break;
        }
    }
}
