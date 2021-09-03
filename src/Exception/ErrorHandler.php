<?php namespace October\Rain\Exception;

use App;
use Request;
use Exception;

/**
 * ErrorHandler handles application exception events.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ErrorHandler
{
    /**
     * @var \System\Classes\ExceptionBase activeMask used to mask any exception fired.
     */
    protected static $activeMask;

    /**
     * @var array maskLayers is  a collection of masks, so multiples can be applied in order.
     */
    protected static $maskLayers = [];

    /**
     * handleException handles all exceptions from the framework workflow. This method will mask
     * any foreign exceptions with a "scent" of the native application's exception, so it can
     * render correctly when displayed on the error page.
     * @param Exception $proposedException The exception candidate that has been thrown.
     * @return mixed Error page contents
     */
    public function handleException(Exception $proposedException)
    {
        // Disable the error handler for test and CLI environment
        if (App::runningUnitTests() || App::runningInConsole()) {
            return;
        }

        // Detect AJAX request and use error 500
        if (Request::ajax()) {
            return $proposedException instanceof AjaxException
                 ? $proposedException->getContents()
                 : static::getDetailedMessage($proposedException);
        }

        $this->beforeHandleError($proposedException);

        // Clear the output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Friendly error pages are used
        if (($customError = $this->handleCustomError()) !== null) {
            return $customError;
        }

        // If the exception is already our brand, use it.
        if ($proposedException instanceof ExceptionBase) {
            $exception = $proposedException;
        }
        // If there is an active mask prepared, use that.
        elseif (static::$activeMask !== null) {
            $exception = static::$activeMask;
            $exception->setMask($proposedException);
        }
        // Otherwise we should mask it with our own default scent.
        else {
            $exception = new ApplicationException($proposedException->getMessage(), 0);
            $exception->setMask($proposedException);
        }

        return $this->handleDetailedError($exception);
    }

    /**
     * applyMask prepares a mask exception to be used when any exception fires.
     * @param Exception $exception The mask exception.
     * @return void
     */
    public static function applyMask(Exception $exception)
    {
        if (static::$activeMask !== null) {
            array_push(static::$maskLayers, static::$activeMask);
        }

        static::$activeMask = $exception;
    }

    /**
     * removeMask destroys the prepared mask by applyMask()
     * @return void
     */
    public static function removeMask()
    {
        if (count(static::$maskLayers) > 0) {
            static::$activeMask = array_pop(static::$maskLayers);
        }
        else {
            static::$activeMask = null;
        }
    }

    /**
     * getDetailedMessage returns a more descriptive error message.
     * @param Exception $exception
     * @return string
     */
    public static function getDetailedMessage($exception)
    {
        return sprintf(
            '"%s" on line %s of %s',
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile()
        );
    }

    //
    // Overrides
    //

    /**
     * beforeHandleError for when we are about to display an error page to the user,
     * provide an opportunity to handle extra functions.
     * @return void
     */
    public function beforeHandleError($exception)
    {
    }

    /**
     * handleCustomError checks if using a custom error page, if so return the contents.
     * Return NULL if a custom error is not set up.
     * @return mixed Error page contents.
     */
    public function handleCustomError()
    {
    }

    /**
     * handleDetailedError displays the detailed system exception page.
     * @return View Object containing the error page.
     */
    public function handleDetailedError($exception)
    {
        return 'Error: ' . $exception->getMessage();
    }
}
