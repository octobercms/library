<?php namespace October\Rain\Network;

use October\Rain\Exception\ApplicationException;

/**
 * Http Network Access is used as a cURL wrapper for the HTTP protocol
 *
 * @package october\network
 * @author Alexey Bobkov, Samuel Georges
 *
 * Usage:
 *
 *   Http::get('http://octobercms.com');
 *   Http::post('...');
 *   Http::delete('...');
 *   Http::patch('...');
 *   Http::put('...');
 *   Http::options('...');
 *
 *   $result = Http::post('http://octobercms.com');
 *   echo $result;                          // Outputs: <html><head><title>...
 *   echo $result->code;                    // Outputs: 200
 *   echo $result->headers['Content-Type']; // Outputs: text/html; charset=UTF-8
 *
 *   Http::post('http://octobercms.com', function($http){
 *
 *       // Sets a HTTP header
 *       $http->header('Rest-Key', '...');
 *
 *       // Set a proxy of type (http, socks4, socks5)
 *       $http->proxy('type', 'host', 'port', 'username', 'password');
 *
 *       // Use basic authentication
 *       $http->auth('user', 'pass');
 *
 *       // Sends data with the request
 *       $http->data('foo', 'bar');
 *       $http->data(['key' => 'value', ...]);
 *
 *       // Disable redirects
 *       $http->noRedirect();
 *
 *       // Check host SSL certificate
 *       $http->verifySSL();
 *
 *       // Sets the timeout duration
 *       $http->timeout(3600);
 *
 *       // Write response to a file
 *       $http->toFile('some/path/to/a/file.txt');
 *
 *       // Sets a cURL option manually
 *       $http->setOption(CURLOPT_SSL_VERIFYHOST, false);
 *
 *   });
 *
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
     * @var string url is the HTTP address to use
     */
    public $url;

    /**
     * @var string method the request should use
     */
    public $method;

    /**
     * @var array headers to be sent with the request
     */
    public $headers = [];

    /**
     * @var callable headerCallbackFunc is a custom function for handling response headers
     */
    public $headerCallbackFunc;

    /**
     * @var string body is the last response body
     */
    public $body = '';

    /**
     * @var string rawBody is the last response body (without headers extracted)
     */
    public $rawBody = '';

    /**
     * @var array code is the last returned HTTP code
     */
    public $code;

    /**
     * @var array info is the cURL response information
     */
    public $info;

    /**
     * @var array requestOptions contains cURL Options
     */
    public $requestOptions;

    /**
     * @var array requestData
     */
    public $requestData;

    /**
     * @var array requestHeaders
     */
    public $requestHeaders;

    /**
     * @var string argumentSeparator
     */
    public $argumentSeparator = '&';

    /**
     * @var string streamFile is the file to use when writing to a file
     */
    public $streamFile;

    /**
     * @var string streamFilter is the filter to apply when writing response to a file
     */
    public $streamFilter;

    /**
     * @var int maxRedirects allowed
     */
    public $maxRedirects = 10;

    /**
     * @var int redirectCount is an internal counter
     */
    protected $redirectCount = null;

    /**
     * @var bool hasFileData determines if files are being sent with the request
     */
    protected $hasFileData = false;

    /**
     * make the object with common properties
     * @param string   $url     HTTP request address
     * @param string   $method  Request method (GET, POST, PUT, DELETE, etc)
     * @param callable $options Callable helper function to modify the object
     */
    public static function make($url, $method, $options = null): Http
    {
        $http = new self;
        $http->url = $url;
        $http->method = $method;

        if ($options && is_callable($options)) {
            $options($http);
        }

        return $http;
    }

    /**
     * get makes a HTTP GET call
     * @param string $url
     * @param array  $options
     * @return self
     */
    public static function get($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_GET, $options);
        return $http->send();
    }

    /**
     * post makes a HTTP POST call
     * @param string $url
     * @param array  $options
     * @return self
     */
    public static function post($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_POST, $options);
        return $http->send();
    }

    /**
     * delete makes a HTTP DELETE call
     * @param string $url
     * @param array  $options
     * @return self
     */
    public static function delete($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_DELETE, $options);
        return $http->send();
    }

    /**
     * patch makes a HTTP PATCH call
     * @param string $url
     * @param array  $options
     * @return self
     */
    public static function patch($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_PATCH, $options);
        return $http->send();
    }

    /**
     * put makes a HTTP PUT call
     * @param string $url
     * @param array  $options
     */
    public static function put($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_PUT, $options);
        return $http->send();
    }

    /**
     * options makes a HTTP OPTIONS call
     * @param string $url
     * @param array  $options
     */
    public static function options($url, $options = null): Http
    {
        $http = self::make($url, self::METHOD_OPTIONS, $options);
        return $http->send();
    }

    /**
     * send the HTTP request
     */
    public function send(): Http
    {
        if (!function_exists('curl_init')) {
            echo 'cURL PHP extension required.'.PHP_EOL;
            exit(1);
        }

        /*
         * Create and execute the cURL Resource
         */
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        if (defined('CURLOPT_FOLLOWLOCATION') && !ini_get('open_basedir')) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, $this->maxRedirects);
        }

        if ($this->requestOptions && is_array($this->requestOptions)) {
            curl_setopt_array($curl, $this->requestOptions);
        }

        /*
         * Set request method
         */
        if ($this->method === self::METHOD_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        elseif ($this->method !== self::METHOD_GET) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        }

        /*
         * Set request data
         */
        if ($this->requestData) {
            if (in_array($this->method, [self::METHOD_POST, self::METHOD_PATCH, self::METHOD_PUT])) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->getRequestData());
            }
            elseif ($this->method === self::METHOD_GET) {
                curl_setopt($curl, CURLOPT_URL, $this->url . '?' . $this->getRequestData());
            }
        }

        /*
         * Set request headers
         */
        if ($this->requestHeaders) {
            $requestHeaders = [];
            foreach ($this->requestHeaders as $key => $value) {
                $requestHeaders[] = $key . ': ' . $value;
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        }

        /*
         * Custom header function
         */
        if ($this->headerCallbackFunc) {
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, $this->headerCallbackFunc);
        }

        /*
         * Execute output to file
         */
        if ($this->streamFile) {
            $stream = fopen($this->streamFile, 'w');
            if ($this->streamFilter) {
                stream_filter_append($stream, $this->streamFilter, STREAM_FILTER_WRITE);
            }
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_FILE, $stream);
            curl_exec($curl);
        }
        /*
         * Execute output to variable
         */
        else {
            $response = $this->rawBody = curl_exec($curl);
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $this->headers = $this->headerToArray(substr($response, 0, $headerSize));
            $this->body = substr($response, $headerSize);
        }

        $this->info = curl_getinfo($curl);
        $this->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        /*
         * Close resources
         */
        curl_close($curl);

        if ($this->streamFile) {
            fclose($stream);
        }

        /*
         * Emulate FOLLOW LOCATION behavior
         */
        if (!defined('CURLOPT_FOLLOWLOCATION') || ini_get('open_basedir')) {
            if ($this->redirectCount === null) {
                $this->redirectCount = $this->maxRedirects;
            }
            if (in_array($this->code, [301, 302])) {
                $this->url = array_get($this->info, 'redirect_url');
                if (!empty($this->url) && $this->redirectCount > 0) {
                    $this->redirectCount -= 1;
                    return $this->send();
                }
            }
        }

        return $this;
    }

    /**
     * getRequestData returns the request data set
     */
    public function getRequestData()
    {
        if (empty($this->requestData)) {
            return isset($this->requestOptions[CURLOPT_POSTFIELDS])
                ? $this->requestOptions[CURLOPT_POSTFIELDS]
                : '';
        }

        if ($this->method === self::METHOD_GET || !$this->hasFileData) {
            return http_build_query($this->requestData, '', $this->argumentSeparator);
        }

        // This will trigger multipart/form-data content type and needs an array,
        // make some attempt at supporting multidimensional array values
        if (is_array($this->requestData)) {
            $out = [];
            foreach ($this->requestData as $var => $dat) {
                $out[$var] = is_array($dat)
                    ? http_build_query($dat, '', $this->argumentSeparator)
                    : $dat;
            }
            return $out;
        }

        return $this->requestData;
    }

    /**
     * data added to the request
     */
    public function data($key, $value = null): Http
    {
        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                $this->data($_key, $_value);
            }
            return $this;
        }

        $this->requestData[$key] = $value;

        return $this;
    }

    /**
     * dataFile added to the request
     */
    public function dataFile(string $key, string $filePath): Http
    {
        $this->hasFileData = true;

        return $this->data($key, curl_file_create($filePath));
    }

    /**
     * header added to the request
     * @param string $value
     */
    public function header($key, $value = null): Http
    {
        if (is_array($key)) {
            foreach ($key as $_key => $_value) {
                $this->header($_key, $_value);
            }
            return $this;
        }

        $this->requestHeaders[$key] = $value;

        return $this;
    }

    /**
     * proxy to use with this request
     */
    public function proxy($type, $host, $port, $username = null, $password = null): Http
    {
        if ($type === 'http') {
            $this->setOption(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        elseif ($type === 'socks4') {
            $this->setOption(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }
        elseif ($type === 'socks5') {
            $this->setOption(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }

        $this->setOption(CURLOPT_PROXY, $host . ':' . $port);

        if ($username && $password) {
            $this->setOption(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        }

        return $this;
    }

    /**
     * auth adds authentication to the request
     * @param string $user
     * @param string $pass
     */
    public function auth($user, $pass = null): Http
    {
        if (strpos($user, ':') !== false && !$pass) {
            list($user, $pass) = explode(':', $user);
        }

        $this->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOption(CURLOPT_USERPWD, $user . ':' . $pass);

        return $this;
    }

    /**
     * noRedirect disables follow location (redirects)
     */
    public function noRedirect(): Http
    {
        if (defined('CURLOPT_FOLLOWLOCATION') && !ini_get('open_basedir')) {
            $this->setOption(CURLOPT_FOLLOWLOCATION, false);
        }
        else {
            $this->maxRedirects = 0;
        }

        return $this;
    }

    /**
     * verifySSL enabled for the request
     */
    public function verifySSL(): Http
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->setOption(CURLOPT_SSL_VERIFYHOST, true);
        return $this;
    }

    /**
     * timeout for the request
     * @param string $timeout
     */
    public function timeout($timeout): Http
    {
        $this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);
        $this->setOption(CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * toFile write the response to a file
     * @param  string $path   Path to file
     * @param  string $filter Stream filter as listed in stream_get_filters()
     */
    public function toFile($path, $filter = null): Http
    {
        $this->streamFile = $path;

        if ($filter) {
            $this->streamFilter = $filter;
        }

        return $this;
    }

    /**
     * headerCallback sets a custom method for handling headers
     *
     *     function header_callback($curl, string $headerLine) {}
     *
     */
    public function headerCallback($callback): Http
    {
        $this->headerCallbackFunc = $callback;

        return $this;
    }

    /**
     * setOption as a single option to the request
     * @param string $option
     * @param string $value
     */
    public function setOption($option, $value = null): Http
    {
        if (is_array($option)) {
            foreach ($option as $_option => $_value) {
                $this->setOption($_option, $_value);
            }
            return $this;
        }

        if (is_string($option) && defined($option)) {
            $optionKey = constant($option);
            $this->requestOptions[$optionKey] = $value;
        }
        elseif (is_int($option)) {
            $constants = get_defined_constants(true);
            $curlOptConstants = array_flip(array_filter($constants['curl'], function ($key) {
                return strpos($key, 'CURLOPT_') === 0;
            }, ARRAY_FILTER_USE_KEY));

            if (isset($curlOptConstants[$option])) {
                $this->requestOptions[$option] = $value;
            }
            else {
                throw new ApplicationException('$option parameter must be a CURLOPT constant or equivalent integer');
            }
        }
        else {
            throw new ApplicationException('$option parameter must be a CURLOPT constant or equivalent integer');
        }

        return $this;
    }

    /**
     * Handy if this object is called directly.
     * @return string The last response.
     */
    public function __toString()
    {
        return (string) $this->body;
    }

    /**
     * headerToArray turns a header string into an array
     */
    protected function headerToArray(string $header): array
    {
        $headers = [];
        $parts = explode("\r\n", $header);

        foreach ($parts as $singleHeader) {
            $delimiter = strpos($singleHeader, ': ');
            if ($delimiter !== false) {
                $key = substr($singleHeader, 0, $delimiter);
                $val = substr($singleHeader, $delimiter + 2);
                $headers[$key] = $val;
            }
            else {
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
}
