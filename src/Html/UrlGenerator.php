<?php namespace October\Rain\Html;

use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

class UrlGenerator extends UrlGeneratorBase
{
    /**
     * Forcing relative links.
     *
     * @var bool
     */
    protected $forcedRelative = false;

    /**
     * Forces all links to be generated relative to the base URL.
     *
     * @param  bool  $value
     * @return void
     */
    public function forceRelative($value = true)
    {
        $this->forcedRelative = $value;
    }

    /**
     * Get whether or not relative URLs are being forced.
     *
     * @return boolean
     */
    public function forcingRelative()
    {
        return $this->forcedRelative;
    }

    /**
     * Get the full URL for the current request.
     *
     * @return string
     */
    public function full($path = null)
    {
        if (is_null($path)) {
            return $this->request->fullUrl();
        }

        $forcingRelative = $this->forcingRelative();
        if ($forcingRelative) $this->forceRelative(false);

        $url = $this->to($path);

        if ($forcingRelative) $this->forceRelative(true);
        return $url;
    }

    /**
     * Get the base URL for the request.
     *
     * @param  string  $scheme
     * @param  string  $root
     * @return string
     */
    protected function getRootUrl($scheme, $root = null)
    {
        if ($this->forcedRelative) {
            return $this->request->getBaseUrl();
        }

        return parent::getRootUrl($scheme, $root);
    }

    /**
     * Format the given URL segments into a single URL.
     *
     * @param  string  $root
     * @param  string  $path
     * @param  string  $tail
     * @return string
     */
    protected function trimUrl($root, $path, $tail = '')
    {
        $url = parent::trimUrl($root, $path, $tail);

        if ($this->forcedRelative) {
            $url = '/' . $url;
        }

        return $url;
    }
}
