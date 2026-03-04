<?php namespace October\Rain\Html;

use File;
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

    /**
     * assetVersion takes a disk path, resolves it to a public URL, and appends
     * a cache-busting version query string based on the file's modification time.
     *
     * Supports path symbols: ~ (base), $ (plugins), # (themes)
     *
     *     Url::assetVersion('~/themes/demo/assets/js/app.js')
     *     // → http://localhost/themes/demo/assets/js/app.js?v1a2b3c4d
     */
    public function assetVersion(string $path): string
    {
        // External URLs pass through unchanged
        if (str_starts_with($path, '//') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Already has a query string, skip versioning
        if (str_contains($path, '?')) {
            return $path;
        }

        // Resolve path symbols (~, $, #) to filesystem path
        $filePath = File::symbolizePath($path);

        // Ensure absolute filesystem path
        if (!str_starts_with($filePath, base_path())) {
            $filePath = base_path(ltrim($filePath, '/'));
        }

        // Compute version hash from file modification time
        if (is_file($filePath)) {
            $version = hash('crc32', (string) filemtime($filePath));
        }
        else {
            $version = hash('crc32', (string) filemtime(base_path('vendor/autoload.php')));
        }

        // Convert disk path to public-facing relative path
        $publicPath = $filePath;
        $basePath = base_path();
        if (str_starts_with($publicPath, $basePath)) {
            $publicPath = substr($publicPath, strlen($basePath));
        }

        // Normalize directory separators for URL
        $publicPath = str_replace('\\', '/', $publicPath);

        // Generate public URL (respects app.asset_url for CDN/S3)
        $url = $this->provider->asset($publicPath);

        return $url . '?v' . $version;
    }
}
