<?php namespace October\Rain\Auth;

use Config;
use October\Rain\Exception\ApplicationException;

/**
 * Used when user authentication fails. Implements a softer error message.
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
class AuthException extends ApplicationException
{
    /**
     * @var boolean Use less specific error messages.
     */
    protected $softErrors = false;

    /**
     * @var string Default soft error message.
     */
    protected $errorMessage = 'The details you entered did not match our records. Please double-check and try again.';

    /**
     * Softens a detailed authentication error with a more vague message when
     * the application is not in debug mode. This is for security reasons.
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $this->softErrors = !Config::get('app.debug', false);

        if ($this->softErrors) {
            $message = $this->errorMessage;
        }

        parent::__construct($message, $code, $previous);
    }
}