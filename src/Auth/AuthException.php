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
     * @var boolean softErrors uses less specific error messages
     */
    protected $softErrors = false;

    /**
     * @var string errorMessage default soft error message
     */
    protected static $errorMessage = 'The details you entered did not match our records. Please double-check and try again.';

    /**
     * __construct softens a detailed authentication error with a more vague message when
     * the application is not in debug mode. This is for security reasons.
     * @param string $message Error message.
     * @param int $code Error code.
     * @param Exception $previous Previous exception.
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $this->softErrors = !Config::get('app.debug', false);

        if ($this->softErrors) {
            $message = static::$errorMessage;
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * setDefaultErrorMessage will override the soft error message displayed to the user
     */
    public static function setDefaultErrorMessage(string $message): void
    {
        static::$errorMessage = $message;
    }
}
