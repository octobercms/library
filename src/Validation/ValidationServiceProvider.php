<?php namespace October\Rain\Validation;

use Illuminate\Validation\ValidationServiceProvider as ValidationServiceProviderBase;

/**
 * ValidationServiceProvider extends the Laravel validation package.
 */
class ValidationServiceProvider extends ValidationServiceProviderBase
{
    /**
     * registerValidationFactory is identical logic of the parent class
     * but replaces the instance with our own factory.
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
