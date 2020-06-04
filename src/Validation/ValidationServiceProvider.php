<?php namespace October\Rain\Validation;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Validation\ValidationServiceProvider as BaseServiceProvider;

/**
 * October CMS wrapper for the Laravel Validation service provider.
 */
class ValidationServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    /**
     * Register the validation factory.
     *
     * @return void
     */
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence of
            // values in a given data collection which is typically a relational database or
            // other persistent data stores. It is used to check for "uniqueness" as well.
            if (isset($app['db'], $app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
