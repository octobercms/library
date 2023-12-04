<?php namespace October\Rain\Html;

use Str;
use Config;
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
        $this->registerUrlGeneratorPolicy();
        $this->registerRelativeHelper();
        $this->registerPjaxCached();
    }

    /**
     * registerUrlGeneratorPolicy controls how URL links are generated throughout the application.
     *
     * detect   - detect hostname and use the current schema
     * secure   - detect hostname and force HTTPS schema
     * insecure - detect hostname and force HTTP schema
     * force    - force hostname and schema using app.url config value
     */
    public function registerUrlGeneratorPolicy()
    {
        $provider = $this->app['url'];
        $policy = Config::get('system.link_policy', 'detect');

        switch (strtolower($policy)) {
            case 'force':
                $appUrl = Config::get('app.url');
                $schema = Str::startsWith($appUrl, 'http://') ? 'http' : 'https';
                $provider->forceRootUrl($appUrl);
                $provider->forceScheme($schema);
                break;

            case 'insecure':
                $provider->forceScheme('http');
                break;

            case 'secure':
                $provider->forceScheme('https');
                break;
        }
    }

    /**
     * registerRelativeHelper
     */
    public function registerRelativeHelper()
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
     * registerPjaxCached
     */
    public function registerPjaxCached()
    {
        $provider = $this->app['request'];

        $provider->macro('pjaxCached', function() use ($provider) {
            return $provider->headers->get('X-PJAX-CACHED') == true;
        });
    }
}
