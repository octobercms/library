<?php namespace October\Rain\Assetic\Asset;

use October\Rain\Assetic\Filter\FilterInterface;
use October\Rain\Assetic\Util\VarUtils;
use InvalidArgumentException;
use RuntimeException;

/**
 * HttpAsset represents an asset loaded via an HTTP request.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class HttpAsset extends BaseAsset
{
    /**
     * @var mixed sourceUrl
     */
    protected $sourceUrl;

    /**
     * @var mixed ignoreErrors
     */
    protected $ignoreErrors;

    /**
     * __construct.
     *
     * @param string  $sourceUrl    The source URL
     * @param array   $filters      An array of filters
     * @param bool    $ignoreErrors
     * @param array   $vars
     *
     * @throws InvalidArgumentException If the first argument is not an URL
     */
    public function __construct($sourceUrl, $filters = [], $ignoreErrors = false, array $vars = array())
    {
        if (strpos($sourceUrl, '//') === 0) {
            $sourceUrl = 'http:'.$sourceUrl;
        }
        elseif (strpos($sourceUrl, '://') === false) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid URL.', $sourceUrl));
        }

        $this->sourceUrl = $sourceUrl;
        $this->ignoreErrors = $ignoreErrors;

        list($scheme, $url) = explode('://', $sourceUrl, 2);
        list($host, $path) = explode('/', $url, 2);

        parent::__construct($filters, $scheme.'://'.$host, $path, $vars);
    }

    /**
     * load
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        $content = @file_get_contents(
            VarUtils::resolve($this->sourceUrl, $this->getVars(), $this->getValues())
        );

        if (false === $content && !$this->ignoreErrors) {
            throw new RuntimeException(sprintf('Unable to load asset from URL "%s"', $this->sourceUrl));
        }

        $this->doLoad($content, $additionalFilter);
    }

    /**
     * getLastModified
     */
    public function getLastModified()
    {
        if (false !== @file_get_contents($this->sourceUrl, false, stream_context_create(array('http' => array('method' => 'HEAD'))))) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Last-Modified: ') === 0) {
                    list(, $mtime) = explode(':', $header, 2);

                    return strtotime(trim($mtime));
                }
            }
        }
    }
}
