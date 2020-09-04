<?php namespace October\Rain\Exception;

use Exception;
use October\Rain\Html\HtmlBuilder;

/**
 * This class represents a critical system exception.
 * System exceptions are logged in the error log.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges, Luke Towers
 */
class SystemException extends ExceptionBase
{
    /**
     * Override the constructor to escape all messages to protect against potential XSS
     * from user provided inputs being included in the exception message
     *
     * @param string $message Error message.
     * @param int $code Error code.
     * @param Exception $previous Previous exception.
     */
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $message = HtmlBuilder::clean($message);

        parent::__construct($message, $code, $previous);
    }
}
