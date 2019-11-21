<?php

use October\Rain\Exception\ApplicationException;
use October\Rain\Network\Http;

class HttpTest extends TestCase
{
    /**
     * Http object fixture
     *
     * @var \October\Rain\Network\Http
     */
    protected $Http;

    public function setUp()
    {
        $this->Http = new Http;
    }

    public function testSetOptionsViaConstants()
    {
        $this->Http->setOption(CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        $this->Http->setOption(CURLOPT_PIPEWAIT, false);
        $this->Http->setOption(CURLOPT_VERBOSE, true);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaStrings()
    {
        $this->Http->setOption('CURLOPT_DNS_USE_GLOBAL_CACHE', true);
        $this->Http->setOption('CURLOPT_PIPEWAIT', false);
        $this->Http->setOption('CURLOPT_VERBOSE', true);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaIntegers()
    {
        $this->Http->setOption(91, true); //CURLOPT_DNS_USE_GLOBAL_CACHE
        $this->Http->setOption(237, false); //CURLOPT_PIPEWAIT
        $this->Http->setOption(41, true); //CURLOPT_VERBOSE

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetInvalidOptionViaString()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption('CURLOPT_SOME_RANDOM_CONSTANT', true);
    }

    public function testSetInvalidOptionViaInteger()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption(99999, true);
    }

    public function testSetOptionsViaArrayOfConstants()
    {
        $this->Http->setOption([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaArrayOfIntegers()
    {
        $this->Http->setOption([
            91 => true, //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true //CURLOPT_VERBOSE
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetOptionsViaArrayOfStrings()
    {
        $this->Http->setOption([
            'CURLOPT_DNS_USE_GLOBAL_CACHE' => true,
            'CURLOPT_PIPEWAIT' => false,
            'CURLOPT_VERBOSE' => true
        ]);

        $this->assertArraySubset([
            CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            CURLOPT_PIPEWAIT => false,
            CURLOPT_VERBOSE => true
        ], $this->Http->requestOptions);
    }

    public function testSetInvalidOptionViaArrayOfStrings()
    {
        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('$option parameter must be a CURLOPT constant or equivalent integer');

        $this->Http->setOption([
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

        $this->Http->setOption([
            91 => true, //CURLOPT_DNS_USE_GLOBAL_CACHE
            237 => false, //CURLOPT_PIPEWAIT
            41 => true, //CURLOPT_VERBOSE
            99999 => true // Invalid CURLOPT integer
        ]);
    }

    public function testSetRequestData()
    {
        $this->Http->data('foo', 'bar');
        $this->assertEquals('foo=bar', $this->Http->getRequestData());
    }

    public function testSetRequestDataArray()
    {
        $this->Http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $this->assertEquals('foo=bar&bar=foo', $this->Http->getRequestData());
    }

    public function testSetPostFields()
    {
        $this->Http->setOption(CURLOPT_POSTFIELDS, 'foobar');
        $this->assertEquals('foobar', $this->Http->getRequestData());
    }

    public function testRequestDataOverridePostFields()
    {
        $this->Http->data([
            'foo' => 'bar',
            'bar' => 'foo'
        ]);
        $this->Http->setOption(CURLOPT_POSTFIELDS, 'foobar');
        $this->assertEquals('foo=bar&bar=foo', $this->Http->getRequestData());
    }
}
