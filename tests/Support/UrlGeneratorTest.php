<?php
use October\Rain\Router\UrlGenerator;

class UrlGeneratorTest extends TestCase
{
    public function testSimpleUrl()
    {
        $this->assertEquals('https://octobercms.com/', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'octobercms.com',
            'path' => '/'
        ]));

        $this->assertEquals('https://octobercms.com/', http_build_url([
            'scheme' => 'https',
            'host' => 'octobercms.com',
            'path' => '/'
        ]));
    }

    public function testComplexUrl()
    {
        $this->assertEquals('https://user:pass@github.com:80/octobercms/october?test=1&test=2#comment1', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/octobercms/october',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ]));

        $this->assertEquals('https://user:pass@github.com:80/octobercms/october?test=1&test=2#comment1', http_build_url([
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/octobercms/october',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ]));
    }

    public function testReplacements()
    {
        $this->assertEquals('https://octobercms.com', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'wordpress.org'
        ], [
            'scheme' => 'https',
            'host' => 'octobercms.com'
        ]));

        $this->assertEquals('https://octobercms.com:80/changelog', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'octobercms.com'
        ], [
            'port' => 80,
            'path' => '/changelog'
        ]));

        $this->assertEquals('ftp://username:password@ftp.test.com.au:21/newfolder', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/octobercms/october',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ], [
            'scheme' => 'ftp',
            'user' => 'username',
            'pass' => 'password',
            'host' => 'ftp.test.com.au',
            'port' => 21,
            'path' => 'newfolder',
            'query' => '',
            'fragment' => ''
        ]));
    }

    public function testJoinSegments()
    {
        $this->assertEquals('https://octobercms.com/plugins/rainlab-pages', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'octobercms.com',
            'path' => '/plugins'
        ], [
            'path' => '/rainlab-pages'
        ], HTTP_URL_JOIN_PATH));

        $this->assertEquals('https://octobercms.com/?query1=1&query2=2&query3=3', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'octobercms.com',
            'path' => '/',
            'query' => 'query1=1&query2=2'
        ], [
            'query' => 'query3=3'
        ], HTTP_URL_JOIN_QUERY));

        $this->assertEquals('https://octobercms.com/plugins/rainlab-pages?query1=1&query2=2&query3=3', UrlGenerator::buildUrl([
            'scheme' => 'https',
            'host' => 'octobercms.com',
            'path' => '/plugins',
            'query' => 'query1=1&query2=2'
        ], [
            'path' => '/rainlab-pages',
            'query' => 'query3=3'
        ], HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY));
    }

    public function testStripSegments()
    {
        $segments = [
            'scheme' => 'https',
            'user' => 'user',
            'pass' => 'pass',
            'host' => 'github.com',
            'port' => 80,
            'path' => '/octobercms/october',
            'query' => 'test=1&test=2',
            'fragment' => 'comment1'
        ];

        $this->assertEquals(
            'https://github.com:80/octobercms/october?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_AUTH)
        );

        $this->assertEquals(
            'https://github.com',
            http_build_url($segments, [], HTTP_URL_STRIP_ALL)
        );

        $this->assertEquals(
            'https://github.com:80/octobercms/october?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_USER)
        );

        $this->assertEquals(
            'https://user@github.com:80/octobercms/october?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PASS)
        );

        $this->assertEquals(
            'https://user:pass@github.com/octobercms/october?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PORT)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80?test=1&test=2#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_PATH)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80/octobercms/october#comment1',
            http_build_url($segments, [], HTTP_URL_STRIP_QUERY)
        );

        $this->assertEquals(
            'https://user:pass@github.com:80/octobercms/october?test=1&test=2',
            http_build_url($segments, [], HTTP_URL_STRIP_FRAGMENT)
        );

        $this->assertEquals(
            'https://user:pass@github.com/octobercms/october',
            http_build_url($segments, [], HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT)
        );
    }
}
