<?php namespace October\Rain\Exception;

/**
 * ForbiddenException represents a permission denied exception and
 * these will redirect to the nearest access denied / 403 page.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ForbiddenException extends ExceptionBase
{
    /**
     * getSafeMessage returns a message that is safe to show to users.
     */
    public function getSafeMessage()
    {
        return $this->getMessage() ?: __("Access Denied");
    }
}
