<?php namespace October\Rain\Network;

/**
 * Intercept only requests made by HttpTransportTest; other tests use real cURL.
 */
class TestCurlTransport
{
    public static $active = false;
    public static $options = [];
    public static $response = "HTTP/1.1 200 OK\r\n\r\nverified";
}

function curl_init()
{
    if (!TestCurlTransport::$active) {
        return \curl_init();
    }
    TestCurlTransport::$options = [];
    return new \stdClass;
}

function curl_setopt($handle, $option, $value)
{
    if (!TestCurlTransport::$active) {
        return \curl_setopt($handle, $option, $value);
    }
    TestCurlTransport::$options[$option] = $value;
    return true;
}

function curl_setopt_array($handle, $options)
{
    if (!TestCurlTransport::$active) {
        return \curl_setopt_array($handle, $options);
    }
    foreach ($options as $option => $value) {
        curl_setopt($handle, $option, $value);
    }
    return true;
}

function curl_exec($handle)
{
    return TestCurlTransport::$active ? TestCurlTransport::$response : \curl_exec($handle);
}

function curl_getinfo($handle, $option = null)
{
    if (!TestCurlTransport::$active) {
        return $option === null ? \curl_getinfo($handle) : \curl_getinfo($handle, $option);
    }
    if ($option === CURLINFO_HEADER_SIZE) {
        return strlen("HTTP/1.1 200 OK\r\n\r\n");
    }
    if ($option === CURLINFO_RESPONSE_CODE) {
        return 200;
    }
    return [];
}

function curl_close($handle)
{
    if (!TestCurlTransport::$active) {
        \curl_close($handle);
    }
}
