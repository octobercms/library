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
}
