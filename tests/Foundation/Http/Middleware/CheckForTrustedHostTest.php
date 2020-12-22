<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use October\Rain\Router\UrlGenerator;
use October\Rain\Foundation\Http\Middleware\CheckForTrustedHost;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * Adaptation of https://github.com/laravel/framework/pull/27206. Credit to @shrft for original implentation.
 */
class CheckForTrustedHostTest extends TestCase
{
    protected static $orignalTrustHosts;

    public static function setUpBeforeClass(): void
    {
        self::$orignalTrustHosts = Request::getTrustedHosts();
    }

    public static function tearDownAfterClass(): void
    {
        Request::setTrustedHosts(self::$orignalTrustHosts);
    }

    public function testTrustedHost()
    {
        $trustedHosts = ['octobercms.com'];
        $headers = ['HOST' => 'octobercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://octobercms.com', $url);
    }

    public function testTrustedHostWwwSubdomain()
    {
        $trustedHosts = ['octobercms.com'];
        $headers = ['HOST' => 'www.octobercms.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://www.octobercms.com', $url);
    }

    public function testTrustedIpHost()
    {
        $trustedHosts = ['127.0.0.1'];
        $headers = ['HOST' => '127.0.0.1'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://127.0.0.1', $url);
    }

    public function testNoTrustedHostsSet()
    {
        $trustedHosts = false;
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');

        $this->assertEquals('http://malicious.com', $url);
    }

    public function testTrustedIpHostWwwSubdomain()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['127.0.0.1'];
        $headers = ['HOST' => 'www.127.0.0.1'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $url = $urlGenerator->to('/');
    }

    public function testThrowExceptionForUntrustedHosts()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['octobercms.com'];
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $urlGenerator->to('/');
    }

    public function testThrowExceptionForUntrustedServerName()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['octobercms.com'];
        $headers = [];
        $servers = ['SERVER_NAME' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    public function testThrowExceptionForUntrustedServerAddr()
    {
        $this->expectException(SuspiciousOperationException::class);

        $trustedHosts = ['octobercms.com'];
        $headers = [];
        $servers = ['SERVER_ADDR' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    protected function createUrlGenerator($trustedHosts = [], $headers = [], $servers = [])
    {
        $middleware = $this->getMockBuilder(CheckForTrustedHost::class)
            ->disableOriginalConstructor()
            ->setMethods(['hosts', 'shouldSpecifyTrustedHosts'])
            ->getMock();

        $middleware->expects($this->any())
            ->method('hosts')
            ->willReturn(CheckForTrustedHost::processTrustedHosts($trustedHosts));

        $middleware->expects($this->any())
            ->method('shouldSpecifyTrustedHosts')
            ->willReturn(true);

        $request = new Request;

        foreach ($headers as $key => $val) {
            $request->headers->set($key, $val);
        }

        foreach ($servers as $key => $val) {
            $request->server->set($key, $val);
        }

        $middleware->handle($request, function () {
        });

        $routes = new RouteCollection;
        $routes->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        return new UrlGenerator($routes, $request);
    }
}
