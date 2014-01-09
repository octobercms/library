<?php namespace October\Rain\Network;

/**
 * HTTP Network Access
 *
 * Used as a cURL wrapper for the HTTP protocol.
 *
 * @package october\network
 * @author Alexey Bobkov, Samuel Georges
 */
class Http
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_PUT = 'PUT';
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * @var array The headers to be sent with the request.
     */
    protected $headers = [];

    /**
     * @var array The headers to be sent with the request.
     */
    protected $options = [];

    /**
     * @var string The method the request should use.
     */
    protected $method;

    /**
     * @var string The last response in its original form.
     */
    protected $lastResponse;

    /**
     * @var string The last response body.
     */
    protected $lastResponseBody;

    /**
     * @var array The last response headers.
     */
    protected $lastResponseHeaders;

    /**
     * @var array The results of curl_getinfo on the last request.
     */
    protected $lastResponseInfo;

    /**
     * Execute the HTTP request.
     * @param string $url
     * @param array $options
     * @return string response body
     */
    protected function send($url, $options = [])
    {
        /*
         * Create and execute the cURL Resource
         */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_URL, $url);

        if (is_array($this->options))
            $options = array_merge($this->options, $options);

        if (!empty($options))
            curl_setopt_array($curl, $options);

        if ($this->method == self::METHOD_POST)
            curl_setopt($curl, CURLOPT_POST, 1);
        elseif ($this->method !== self::METHOD_GET)
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        $response = curl_exec($curl);

        /*
         * Extract the response info, header and body from a cURL response. Saves
         * the data in variables stored on the object.
         */
        $this->lastResponse = $response;
        $this->lastResponseInfo = curl_getinfo($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerText = substr($response, 0, $headerSize);
        $this->lastResponseHeaders = $this->headerToArray($headerText);
        $this->lastResponseBody = substr($response, $headerSize);

        curl_close($curl);
        return $this;
    }

    /**
     * Get all or a specific header from the last curl statement.
     * @param string $header Name of the header to get. If not provided, gets
     * all headers from the last response.
     * @return array
     */
    public function getHeaders($header = null)
    {
        if (!$header) {
            return $this->lastResponseHeaders;
        }

        if (array_key_exists($header, $this->lastResponseHeaders)) {
            return $this->lastResponseHeaders[$header];
        }
    }

    //
    // cURL settings
    //

    /**
     * Add a header to the request.
     * @param string $value
     */
    public function withHeader($value)
    {
        $this->headers[] = $value;
        return $this;
    }

    /**
     * Add a single option to the request.
     * @param string $option
     * @param string $value
     */
    public function withOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Sets the request timeout.
     * @param string $timeout
     */
    public function withTimeout($timeout)
    {
        $this->options[CURLOPT_CONNECTTIMEOUT] = $timeout;
        $this->options[CURLOPT_TIMEOUT] = $timeout;
        return $this;
    }

    /**
     * Disable follow location (redirects)
     */
    public function noRedirect()
    {
        $this->options[CURLOPT_FOLLOWLOCATION] = false;
        return $this;
    }

    /**
     * Sets a proxy to use with this request
     */
    public function withProxy($type, $host, $port, $username = null, $password = null)
    {
        if ($type === 'http')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        elseif ($type === 'socks4')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
        elseif ($type === 'socks5')
            $this->options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;

        $this->options[CURLOPT_PROXY] = $host . ':' . $port;

        if ($username && $password)
            $this->options[CURLOPT_PROXYUSERPWD] = $username . ':' . $password;

        return $this;
    }

    //
    // Protocol methods
    //

    /**
     * Make a HTTP GET call
     * @param string $url
     * @param array $query GET parameters/query string, optional
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string
     */
    public function get($url, $query = [], $options = [])
    {
        $this->method = self::METHOD_GET;
        if (!empty($query)) $url = $this->buildUrl($url, $query);

        return $this->send($url, $options);
    }

    /**
     * Make a HTTP POST call
     * @param string $url
     * @param array $data POST data, optional
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string response body
     */
    public function post($url, $data = [], $options = [])
    {
        $this->method = self::METHOD_POST;
        if (!empty($data)) $this->setPostData($data);

        return $this->send($url, $options);
    }

    /**
     * Make a HTTP DELETE call.
     * @param string $url
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string response body
     */
    public function delete($url, $options = [])
    {
        $this->method = self::METHOD_DELETE;
        return $this->send($url, $options);
    }

    /**
     * Make a HTTP PATCH call.
     * @param string $url
     * @param array $data POST data, optional
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string response body
     */
    public function patch($url, $data = [], $options = [])
    {
        $this->method = self::METHOD_PATCH;
        if (!empty($data)) $this->setPostData($data);

        return $this->send($url, $options);
    }

    /**
     * Make a HTTP PUT call.
     * @param string $url
     * @param array $data POST data, optional
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string response body
     */
    public function put($url, $data = [], $options = [])
    {
        $this->method = self::METHOD_PUT;
        if (!empty($data)) $this->setPostData($data);
        return $this->send($url, $options);
    }

    /**
     * Make a HTTP OPTIONS call.
     * @param string $url
     * @param array $options cURL options (curl_setopt_array), optional
     * @return string response body
     */
    public function options($url, $options = [])
    {
        $this->method = self::METHOD_OPTIONS;
        return $this->send($url, $options);
    }

    //
    // Helpers
    //

    /**
     * Get info about the last executed curl statement.
     * @return mixed
     */
    public function getResponseInfo()
    {
        return $this->lastResponseInfo;
    }

    /**
     * Build an URL with an optional query string.
     * @param string $url the base URL without any query string
     * @param array $query array of GET parameters
     * @return string
     */
    public function buildUrl($url, $query)
    {
        /*
         * Append query string
         */
        if (!empty($query)) {
            $queryString = http_build_query($query);
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * Set the POST data of the call.
     * @param $data
     */
    protected function setPostData($data)
    {
        $postData = http_build_query($data);
        $this->options[CURLOPT_POSTFIELDS] = $postData;
    }

    /**
     * Turn a header string into an array.
     * @param string $header
     * @return array
     */
    protected function headerToArray($header)
    {
        $tmp = explode("\r\n", $header);
        $headers = [];
        foreach ($tmp as $singleHeader) {
            $delimiter = strpos($singleHeader, ': ');
            if ($delimiter !== false) {
                $key = substr($singleHeader, 0, $delimiter);
                $val = substr($singleHeader, $delimiter + 2);
                $headers[$key] = $val;
            } else {
                $delimiter = strpos($singleHeader, ' ');
                if ($delimiter !== false) {
                    $key = substr($singleHeader, 0, $delimiter);
                    $val = substr($singleHeader, $delimiter + 1);
                    $headers[$key] = $val;
                }
            }
        }
        return $headers;
    }

    /**
     * Handy if this object is called directly.
     * @return string The last response.
     */
    public function __toString()
    {
        return $this->lastResponseBody;
    }
}