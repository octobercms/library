<?php namespace October\Rain\Exception;

/**
 * ApplicationException represents an application exception and
 * these are not reported in the error log.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ApplicationException extends ExceptionBase
{
    /**
     * getSafeMessage returns a message that is safe to show to users.
     */
    public function getSafeMessage()
    {
        return $this->getMessage() ?: __("An Error Occurred");
    }
}
