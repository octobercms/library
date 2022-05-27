<?php namespace October\Rain\Html;

use Str;
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
        $policy = $this->app['config']->get('system.link_policy', 'detect');

        switch (strtolower($policy)) {
            case 'force':
                $appUrl = $this->app['config']->get('app.url');
                $schema = Str::startsWith($appUrl, 'http://') ? 'http' : 'https';
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

    /**
     * registerRelativeHelper
     */
    public function registerRelativeHelper()
    {
        $provider = $this->app['url'];

        $provider->macro('toRelative', function($url) use ($provider) {
            return parse_url($provider->to($url), PHP_URL_PATH);
        });
    }
}
