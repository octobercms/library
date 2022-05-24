<?php namespace October\Rain\Foundation\Exception;

use Log;
use Event;
use Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Exception\NotFoundException;
use October\Rain\Exception\AjaxException;
use ReflectionFunction;
use ReflectionClass;
use Throwable;
use Exception;
use Closure;

/**
 * Handler is the core exception handler
 */
class Handler extends ExceptionHandler
{
    /**
     * @var array dontReport these exception types.
     */
    protected $dontReport = [
        \October\Rain\Exception\AjaxException::class,
        \October\Rain\Exception\NotFoundException::class,
        \October\Rain\Exception\ForbiddenException::class,
        \October\Rain\Exception\ValidationException::class,
        \October\Rain\Exception\ApplicationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ];

    /**
     * @var array handlers for registered exceptions.
     */
    protected $handlers = [];

    /**
     * report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        if (!$this->hasBootedEvents()) {
            return;
        }

        /**
         * @event exception.beforeReport
         * Fires before the exception has been reported
         *
         * Example usage (prevents the reporting of a given exception)
         *
         *     Event::listen('exception.beforeReport', function (\Exception $exception) {
         *         if ($exception instanceof \My\Custom\Exception) {
         *             return false;
         *         }
         *     });
         */
        if (Event::fire('exception.beforeReport', [&$exception], true) === false) {
            return;
        }

        $exception = $this->mapException($exception);

        if ($this->shouldntReport($exception)) {
            return;
        }

        if (class_exists('Log')) {
            Log::error($exception);
        }

        /**
         * @event exception.report
         * Fired after the exception has been reported
         *
         * Example usage (performs additional reporting on the exception)
         *
         *     Event::listen('exception.report', function (\Exception $exception) {
         *         App::make('sentry')->captureException($exception);
         *     });
         */
        Event::fire('exception.report', [$exception]);
    }

    /**
     * render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // Exception occured before system has booted
        if (!$this->hasBootedEvents()) {
            return parent::render($request, $exception);
        }

        // Exception wants to return its own response
        if ($exception instanceof Responsable) {
            return $exception->toResponse($request);
        }

        // Convert to public-friendly exception
        $exception = $this->prepareException($this->mapException($exception));
        $statusCode = $this->getStatusCode($exception);

        // Custom handlers
        if ($response = $this->callCustomHandlers($exception)) {
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                return $response;
            }

            if (!is_null($response)) {
                return Response::make($response, $statusCode);
            }
        }

        /**
         * @event exception.beforeRender
         * Fired before the exception renders and returns an optional custom response.
         *
         * Example usage
         *
         *     Event::listen('exception.beforeRender', function (\Exception $exception) {
         *         return 'An error happened!';
         *     });
         */
        if ($event = Event::fire('exception.beforeRender', [$exception, $statusCode, $request], true)) {
            return Response::make($event, $statusCode);
        }

        return parent::render($request, $exception);
    }

    /**
     * prepareException for rendering.
     */
    protected function prepareException(Throwable $e)
    {
        $e = parent::prepareException($e);

        if ($e instanceof NotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        }

        return $e;
    }

    /**
     * getStatusCode checks if the exception implements the HttpExceptionInterface, or returns
     * as generic 500 error code for a server side error.
     * @param \Exception $exception
     * @return int
     */
    protected function getStatusCode($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        }
        elseif ($exception instanceof ForbiddenException) {
            $code = 403;
        }
        elseif ($exception instanceof AjaxException) {
            $code = 406;
        }
        else {
            $code = 500;
        }

        return $code;
    }

    /**
     * context is the the default context variables for logging.
     *
     * @return array
     */
    protected function context()
    {
        return [];
    }

    //
    // Custom handlers
    //

    /**
     * error registers an application error handler.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        array_unshift($this->handlers, $callback);
    }

    /**
     * callCustomHandlers handles the given exception.
     *
     * @param  \Exception  $exception
     * @param  bool  $fromConsole
     * @return void
     */
    protected function callCustomHandlers($exception, $fromConsole = false)
    {
        foreach ($this->handlers as $handler) {
            // If this exception handler does not handle the given exception, we will just
            // go the next one. A handler may type-hint an exception that it handles so
            //  we can have more granularity on the error handling for the developer.
            if (!$this->handlesException($handler, $exception)) {
                continue;
            }

            $code = $this->getStatusCode($exception);

            // We will wrap this handler in a try / catch and avoid white screens of death
            // if any exceptions are thrown from a handler itself. This way we will get
            // at least some errors, and avoid errors with no data or not log writes.
            try {
                $response = $handler($exception, $code, $fromConsole);
            }
            catch (Exception $e) {
                $response = $this->convertExceptionToResponse($e);
            }
            // If this handler returns a "non-null" response, we will return it so it will
            // get sent back to the browsers. Once the handler returns a valid response
            // we will cease iterating through them and calling these other handlers.
            if (isset($response) && ! is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * handlesException determine if the given handler handles this exception.
     * @param  \Closure    $handler
     * @param  \Exception  $exception
     * @return bool
     */
    protected function handlesException(Closure $handler, $exception)
    {
        $reflection = new ReflectionFunction($handler);
        return $reflection->getNumberOfParameters() === 0 || $this->hints($reflection, $exception);
    }

    /**
     * hints determines if the given handler type hints the exception.
     * @param  \ReflectionFunction  $reflection
     * @param  \Exception  $exception
     * @return bool
     */
    protected function hints(ReflectionFunction $reflection, $exception)
    {
        $parameters = $reflection->getParameters();
        $expected = $parameters[0];

        try {
            return (new ReflectionClass($expected->getType()->getName()))->isInstance($exception);
        }
        catch (Throwable $t) {
            return false;
        }
    }

    /**
     * hasBootedEvents checks if we can broadcast events
     */
    protected function hasBootedEvents(): bool
    {
        if (!class_exists('Event')) {
            return false;
        }

        if (!$app = Event::getFacadeApplication()) {
            return false;
        }

        if (!$app->bound('events')) {
            return false;
        }

        return true;
    }
}
