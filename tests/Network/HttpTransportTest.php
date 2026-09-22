<?php

use October\Rain\Network\Http;
use October\Rain\Network\TestCurlTransport;

require_once __DIR__.'/fixtures/curl.php';

class HttpTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestCurlTransport::$active = true;
    }

    protected function tearDown(): void
    {
        TestCurlTransport::$active = false;
        TestCurlTransport::$options = [];
        parent::tearDown();
    }

    public function testHttpsRequestsVerifyPeerAndHostnameByDefault()
    {
        $response = Http::get('https://example.test/resource');

        $this->assertSame('verified', $response->body);
        $this->assertSame(true, TestCurlTransport::$options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, TestCurlTransport::$options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testExplicitVerificationUsesTheHostnameVerificationMode()
    {
        Http::get('https://example.test/resource', function ($http) {
            $http->verifySSL();
        });

        $this->assertSame(true, TestCurlTransport::$options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, TestCurlTransport::$options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testExplicitOptionsCanOverrideVerificationDefaults()
    {
        Http::get('https://example.test/resource', function ($http) {
            $http->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $http->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        });

        $this->assertSame(false, TestCurlTransport::$options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(0, TestCurlTransport::$options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testCustomCertificateAuthorityPreservesVerification()
    {
        Http::get('https://example.test/resource', function ($http) {
            $http->setOption(CURLOPT_CAINFO, '/certificates/private-ca.pem');
        });

        $this->assertSame('/certificates/private-ca.pem', TestCurlTransport::$options[CURLOPT_CAINFO]);
        $this->assertSame(true, TestCurlTransport::$options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, TestCurlTransport::$options[CURLOPT_SSL_VERIFYHOST]);
    }

}
