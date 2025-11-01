<?php namespace October\Rain\Html;

use Illuminate\Support\ServiceProvider;

/**
 * UrlServiceProvider
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class UrlServiceProvider extends ServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->registerRelativeHelpers();
        $this->registerRequestHelpers();

        $this->registerUrlGeneratorPolicy();

        $this->app['events']->listen('site.changed', function() {
            $this->registerUrlGeneratorPolicy();
        });
    }

    /**
     * registerUrlGeneratorPolicy controls how URL links are generated throughout the application.
     *
     * detect   - detect hostname and use the current schema
     * secure   - detect hostname and force HTTPS schema
     * insecure - detect hostname and force HTTP schema
     * force    - force hostname and schema using app.url config value
     */
    protected function registerUrlGeneratorPolicy()
    {
        $provider = $this->app['url'];
        $policy = $this->app['config']->get('system.link_policy', 'detect');
        $appUrl = $this->app['config']->get('app.url');

        switch (strtolower($policy)) {
            case 'force':
                $provider->forceRootUrl($appUrl);
                $provider->forceScheme(str_starts_with($appUrl, 'http://') ? 'http' : 'https');
                break;

            case 'insecure':
                $provider->forceScheme('http');
                break;

            case 'secure':
                $provider->forceScheme('https');
                break;
        }

        // Workaround for October CMS installed to a subdirectory since
        // Laravel won't support this use case, related issue:
        // https://github.com/laravel/framework/pull/3918
        if ($this->app->runningInConsole()) {
            $provider->forceRootUrl($appUrl);
        }
    }

    /**
     * registerRelativeHelpers
     */
    protected function registerRelativeHelpers()
    {
        $provider = $this->app['url'];

        $provider->macro('makeRelative', function(...$args) use ($provider) {
            return (new \October\Rain\Html\UrlMixin($provider))->makeRelative(...$args);
        });

        $provider->macro('toRelative', function(...$args) use ($provider) {
            return (new \October\Rain\Html\UrlMixin($provider))->toRelative(...$args);
        });

        $provider->macro('toSigned', function(...$args) use ($provider) {
            return (new \October\Rain\Html\UrlMixin($provider))->toSigned(...$args);
        });
    }

    /**
     * registerRequestHelpers
     */
    protected function registerRequestHelpers()
    {
        $provider = $this->app['request'];

        $provider->macro('pjaxCached', function() use ($provider) {
            return $provider->headers->get('X-PJAX-CACHED') == true;
        });

        $provider->macro('isCrawler', function($userAgent = null) use ($provider) {
            return (new \Jaybizzle\CrawlerDetect\CrawlerDetect($provider->server()))
                ->isCrawler($userAgent)
            ;
        });
    }
}
