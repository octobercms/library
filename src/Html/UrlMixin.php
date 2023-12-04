<?php namespace October\Rain\Html;

use Config;

/**
 * UrlMixin
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class UrlMixin
{
    use \Illuminate\Support\InteractsWithTime;

    /**
     * @var mixed provider
     */
    protected $provider;

    /**
     * __construct
     */
    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * makeRelative converts a full URL to a relative URL
     */
    public function makeRelative($url)
    {
        $fullUrl = $this->provider->to($url);
        return parse_url($fullUrl, PHP_URL_PATH)
            . (($query = parse_url($fullUrl, PHP_URL_QUERY)) ? '?' . $query : '')
            . (($fragment = parse_url($fullUrl, PHP_URL_FRAGMENT)) ? '#' . $fragment : '')
            ?: '/';
    }

    /**
     * toRelative makes a link relative if configuration asks for it
     */
    public function toRelative($url)
    {
        return Config::get('system.relative_links', false)
            ? $this->makeRelative($url)
            : $this->provider->to($url);
    }

    /**
     * toSigned signs a bare URL that can be validated with hasValidSignature
     */
    public function toSigned($url, $expiration = null, $absolute = true)
    {
        if (!$absolute) {
            $url = $this->makeRelative($url);
        }

        $parameters = [];

        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $parameters);

        unset($parameters['signature']);

        ksort($parameters);

        if ($expiration) {
            unset($parameters['expires']);
            $parameters = $parameters + ['expires' => $this->availableAt($expiration)];
        }

        $key = Config::get('app.key');

        $signUrl = http_build_url($url, ['query' => http_build_query($parameters)]);

        $signature = hash_hmac('sha256', $signUrl, $key);

        return http_build_url($url, ['query' => http_build_query($parameters + ['signature' => $signature])]);
    }
}
