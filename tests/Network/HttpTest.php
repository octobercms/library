<?php

use October\Rain\Network\Http;
use October\Rain\Exception\ApplicationException;

class HttpTest extends TestCase
{
    const TEST_URL = 'http://somepath.tld';

    public function testSetOptionsViaConstants()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption(CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        $http->setOption(CURLOPT_PIPEWAIT, false);
        $http->setOption(CURLOPT_VERBOSE, true);

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetOptionsViaStrings()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption('CURLOPT_DNS_USE_GLOBAL_CACHE', true);
        $http->setOption('CURLOPT_PIPEWAIT', false);
        $http->setOption('CURLOPT_VERBOSE', true);

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetOptionsViaIntegers()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption(91, true);   // CURLOPT_DNS_USE_GLOBAL_CACHE
        $http->setOption(237, false); // CURLOPT_PIPEWAIT
        $http->setOption(41, true);   // CURLOPT_VERBOSE

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetInvalidOptionViaString()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption('CURLOPT_SOME_RANDOM_CONSTANT', true);
    }

    public function testSetInvalidOptionViaInteger()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption(99999, true);
    }

    public function testSetOptionsViaArrayOfConstants()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ]);

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetOptionsViaArrayOfIntegers()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption([
            91 => true,   //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true    //CURLOPT_VERBOSE
        ]);

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetOptionsViaArrayOfStrings()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption([
            'CURLOPT_DNS_USE_GLOBAL_CACHE' => true,
            'CURLOPT_PIPEWAIT' => false,
            'CURLOPT_VERBOSE' => true
        ]);

        $this->assertEquals([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $http->requestOptions);
    }

    public function testSetInvalidOptionViaArrayOfStrings()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption([
            'CURLOPT_DNS_USE_GLOBAL_CACHE' => true,
            'CURLOPT_PIPEWAIT' => false,
            'CURLOPT_VERBOSE' => true,
            'CURLOPT_SOME_RANDOM_CONSTANT' => true
        ]);
    }

    public function testSetInvalidOptionViaArrayOfIntegers()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->setOption([
            91 => true,   //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true,   //CURLOPT_VERBOSE
            99999 => true // Invalid CURLOPT integer
        ]);
    }

    public function testSetRequestDataGet()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->data('foo', 'bar');

        $this->assertEquals('foo=bar', $http->getRequestData());
    }

    public function testSetRequestDataPost()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_POST);
        $http->data('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $http->getRequestData());
    }

    public function testSetRequestDataArrayGet()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);

        $this->assertEquals('foo=bar&bar=foo', $http->getRequestData());
    }

    public function testSetRequestDataArrayPost()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_POST);
        $http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);

        $this->assertEquals([
            'foo' => 'bar',
            'bar' => 'foo'
        ], $http->getRequestData());
    }

    public function testSetPostFieldsPost()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_POST);
        $http->setOption(CURLOPT_POSTFIELDS, 'foobar');

        $this->assertEquals('foobar', $http->getRequestData());
    }

    public function testRequestDataOverrideGetFieldsGet()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_GET);
        $http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $http->setOption(CURLOPT_POSTFIELDS, 'foobar');

        $this->assertEquals('foo=bar&bar=foo', $http->getRequestData());
    }

    public function testRequestDataOverrideGetFieldsPost()
    {
        $http = Http::make(self::TEST_URL, Http::METHOD_POST);
        $http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $http->setOption(CURLOPT_POSTFIELDS, 'foobar');

        $this->assertEquals([
            'foo' => 'bar',
            'bar' => 'foo'
        ], $http->getRequestData());
    }
}
