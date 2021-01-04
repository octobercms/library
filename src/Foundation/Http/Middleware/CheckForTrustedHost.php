<?php namespace October\Rain\Foundation\Http\Middleware;

use Config;
use October\Rain\Http\Middleware\TrustHosts as BaseMiddleware;

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
        return self::processTrustedHosts(Config::get('app.trustedHosts', []));
    }

    /**
     * Processes the trusted hosts into an array of patterns the match for host header checks.
     *
     * @param array|bool $hosts
     * @return array
     */
    public static function processTrustedHosts($hosts)
    {
        if ($hosts === true) {
            $url = Config::get('app.url', null);

            // If no app URL is set, then disable trusted hosts.
            if (is_null($url)) {
                return [];
            }

            // Allow both the domain and the `www` subdomain for app.url
            // regardless of the presence of www in the app.url value
            $host = parse_url($url, PHP_URL_HOST);
            if (preg_match('/^www\.(.*?)$/i', $host, $matches)) {
                $host = '^(www\.)?' . preg_quote($matches[1]) . '$';
            } else {
                $host = '^(www\.)?' . preg_quote($host) . '$';
            }

            $hosts = [$host];
        } elseif ($hosts === false) {
            return [];
        }

        $hosts = array_map(function ($host) {
            // If a URL is provided, extract the host
            if (filter_var($host, FILTER_VALIDATE_URL)) {
                $host = parse_url($host, PHP_URL_HOST);
            }

            // Prepare IP address & plain hostname values to be processed by the regex filter
            if (
                filter_var($host, FILTER_VALIDATE_IP)
                || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                return '^' . preg_quote($host) . '$';
            }

            // Allow everything else through as is
            return $host;
        }, $hosts);

        return $hosts;
    }
}
