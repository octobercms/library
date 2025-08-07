<?php namespace October\Rain\Foundation\Bootstrap;

use Config;
use Illuminate\Foundation\Bootstrap\HandleExceptions as HandleExceptionsBase;

/**
 * HandleExceptions is a registration point for handling exceptions
 */
class HandleExceptions extends HandleExceptionsBase
{
    /**
     * shouldIgnoreDeprecationErrors determine if deprecation errors should be ignored.
     *
     * The logic here used by Laravel is unsatisfactory since the MessageLogged event
     * won't distinguish between exceptions and deprecations or the log driver used,
     * so a custom configuration key is included as part of the core system.
     *
     * @return bool
     */
    protected function shouldIgnoreDeprecationErrors()
    {
        if (Config::get('system.log_deprecations', false) === false) {
            return true;
        }

        return parent::shouldIgnoreDeprecationErrors();
    }
}
