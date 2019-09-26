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
}