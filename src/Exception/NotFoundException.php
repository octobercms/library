<?php namespace October\Rain\Exception;

/**
 * NotFoundException represents a missing record exception and
 * these will redirect to the nearest 404 page.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class NotFoundException extends ExceptionBase
{
    /**
     * getSafeMessage returns a message that is safe to show to users.
     */
    public function getSafeMessage()
    {
        return $this->getMessage() ?: __("Not Found");
    }
}
