<?php

use Illuminate\Contracts\Http\Kernel;
use October\Rain\Foundation\Application;
use October\Rain\Foundation\Configuration\ApplicationBuilder;

class ApplicationBuilderTest extends TestCase
{
    public function testWebMiddlewareUsesOctoberDefaultsAndPreservesCustomizations()
    {
        $previous = Illuminate\Container\Container::getInstance();

        try {
            $app = new Application(dirname(__DIR__, 3));
            (new ApplicationBuilder($app))->withKernels()->withMiddleware(function ($middleware) {
                $middleware->appendToGroup('web', 'custom.middleware');
            });
            $web = $app->make(Kernel::class)->getMiddlewareGroups()['web'];

            $this->assertNotContains(Illuminate\Cookie\Middleware\EncryptCookies::class, $web);
            $this->assertNotContains(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $web);
            $this->assertNotContains(Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class, $web);
            $this->assertContains(October\Rain\Foundation\Http\Middleware\EncryptCookies::class, $web);
            $this->assertContains('custom.middleware', $web);
        }
        finally {
            Illuminate\Container\Container::setInstance($previous);
        }
    }
}
