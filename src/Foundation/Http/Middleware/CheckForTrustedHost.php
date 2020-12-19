<?php namespace October\Rain\Foundation\Http\Middleware;

use Config;
use Illuminate\Http\Middleware\TrustHosts as BaseMiddleware;

class CheckForTrustedHost extends BaseMiddleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * Trusted hosts should be defined in the `config/app.php` configuration file as an array, ie.:
     *
     *   'trustedHosts' => [
     *      'example.com',           // Matches just example.com
     *      'www.example.com',       // Matches just www.example.com
     *      '^(.+\.)?example\.com$', // Matches example.com and all subdomains
     *      'https://example.com',   // Matches just example.com
     *   ],
     *
     * or as a boolean - if true, it will trust the `app.url` host and all subdomains, if false it
     * will disable the feature entirely.
     *
     * Hosts can be defined as regex patterns for complex matching.
     *
     * @return array
     */
    public function hosts()
    {
        $hosts = Config::get('app.trustedHosts', []);

        if (is_array($hosts)) {
            foreach ($hosts as &$host) {
                // Allow for people including protocol in their configured hosts
                if (starts_with($host, 'http://') || starts_with($host, 'https://') {
                    $host = parse_url($host, PHP_URL_HOST);
                }

                // Ensure that trusted hosts specified as just a plain hostname are
                // properly converted into a strict hostname matching pattern,
                // otherwise example.com allows sub.example.com
                if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    $host = '^' . preg_quote($host) . '$';
                }
            }
        } elseif ($hosts === true) {
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
