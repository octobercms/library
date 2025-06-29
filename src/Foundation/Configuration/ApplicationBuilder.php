<?php namespace October\Rain\Foundation\Configuration;

use Illuminate\Foundation\Configuration\ApplicationBuilder as ApplicationBuilderBase;
use Illuminate\Foundation\Configuration\Exceptions;

/**
 * ApplicationBuilder foundation class as an extension of Laravel
 */
class ApplicationBuilder extends ApplicationBuilderBase
{
    /**
     * Register the standard kernel classes for the application.
     *
     * @return $this
     */
    public function withKernels()
    {
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \October\Rain\Foundation\Http\Kernel::class
        );

        $this->app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \October\Rain\Foundation\Console\Kernel::class
        );

        return $this;
    }

    /**
     * Register and configure the application's exception handler.
     *
     * @param  callable|null  $using
     * @return $this
     */
    public function withExceptions(?callable $using = null)
    {
        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \October\Rain\Foundation\Exception\Handler::class
        );

        $using ??= fn () => true;

        $this->app->afterResolving(
            \Illuminate\Foundation\Exceptions\Handler::class,
            fn ($handler) => $using(new Exceptions($handler)),
        );

        return $this;
    }

    /**
     * withMiddleware is a modifier to the parent logic to remove unwanted default middleware
     */
    public function withMiddleware(?callable $callback = null)
    {
        $nested = function($middleware) use ($callback) {
            $middleware
                ->remove([
                    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
                    \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
                ])
                ->append([
                    \October\Rain\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
                ])
                ->removeFromGroup('web', [
                    \Illuminate\Cookie\Middleware\EncryptCookies::class,
                    \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                ])
                ->appendToGroup('web', [
                    \October\Rain\Foundation\Http\Middleware\EncryptCookies::class,
                ]);

            if ($callback !== null) {
                $callback($middleware);
            }
        };

        return parent::withMiddleware($nested);
    }
}
