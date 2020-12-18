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
     * Hosts can be defined as regex patterns for complex matching.
     *
     * @return array
     */
    public function hosts()
    {
        return Config::get('app.trustedHosts', []);
    }
}
