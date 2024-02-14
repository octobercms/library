<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Auth as AuthBase;

/**
 * Auth
 *
 * @see \RainLab\User\Classes\AuthManager
 */
class Auth extends AuthBase
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth';
    }
}
