<?php namespace October\Rain\Auth;

use Config;
use October\Rain\Exception\ApplicationException;
use Exception;

/**
 * AuthException used when user authentication fails. Implements a softer error message
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
class AuthException extends ApplicationException
{
    /**
     * @var string errorMessage default soft error message
     */
    protected static $errorMessage = 'The details you entered did not match our records. Please double-check and try again.';

    /**
     * @var array errorCodes for each error distinction.
     */
    protected static $errorCodes = [
        // Input errors
        100 => 'Missing Attribute',
        101 => 'Missing Login Attribute',
        102 => 'Missing Password Attribute',

        // Lookup errors
        200 => 'User Not Found',
        201 => 'Wrong Password',

        // State errors
        300 => 'User Not Activated',
        301 => 'User Suspended',
        302 => 'User Banned',

        // Context errors
        400 => 'User Not Logged In',
        401 => 'User Forbidden',
    ];

    /**
     * __construct softens a detailed authentication error with a more vague message when
     * the application is not in debug mode for security reasons.
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if ($this->useSoftErrors()) {
            $message = static::$errorMessage;
        }

        if (isset(static::$errorCodes[$code])) {
            $this->errorType = static::$errorCodes[$code];
        }

        parent::__construct(__($message), $code, $previous);
    }

    /**
     * setDefaultErrorMessage will override the soft error message displayed to the user
     */
    public static function setDefaultErrorMessage(string $message)
    {
        static::$errorMessage = $message;
    }

    /**
     * useSoftErrors determines if soft errors should be used, set by config and when
     * enabled uses less specific error messages.
     */
    protected function useSoftErrors(): bool
    {
        if (Config::get('system.soft_auth_errors') !== null) {
            return (bool) Config::get('system.soft_auth_errors');
        }

        return !Config::get('app.debug', false);
    }
}
