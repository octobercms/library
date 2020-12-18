<?php namespace October\Rain\Foundation\Http\Middleware;

use Config;
use Illuminate\Http\Middleware\TrustHosts as BaseMiddleware;

class TrustHosts extends BaseMiddleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * Trusted hosts should be defined in the `config/app.php` configuration file as an array, ie.:
     *
     *   'trustedHosts' => [
     *      'site.com',
     *      'www.site.com',
     *      '.*?\.site.com'
     *   ]
     *
     * or as a boolean - if true, it will trust the `app.url` host and all subdomains.
     *
     * Hosts can be defined as regex patterns for complex matching.
     *
     * @return array
     */
    public function hosts()
    {
        $hosts = Config::get('app.trustedHosts', []);

        if ($hosts === true) {
            // Use app.url config value, and all subdomains, as the trusted host
            $url = Config::get('app.url', null);

            if (is_null($url)) {
                return [];
            }

            return [
                '^(.+\.)?' . preg_quote(parse_url($url, PHP_URL_HOST)) . '$',
            ];
        } elseif ($hosts === false) {
            return [];
        }

        return $hosts;
    }
}
