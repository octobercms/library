<?php namespace October\Rain\Foundation\Exception;

use Log;
use Event;
use Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Reflector;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Exception\NotFoundException;
use October\Rain\Exception\AjaxException;
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
        if (Event::fire('exception.beforeReport', [$exception], true) === false) {
            return;
        }

        $exception = $this->mapException($exception);

        if ($this->shouldntReport($exception)) {
            return;
        }

        if (Reflector::isCallable($reportCallable = [$exception, 'report']) &&
            $this->container->call($reportCallable) !== false) {
            return;
        }

        foreach ($this->reportCallbacks as $reportCallback) {
            if ($reportCallback->handles($exception) && $reportCallback($exception) === false) {
                return;
            }
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
        // Exception occurred before system has booted
        if (!$this->hasBootedEvents()) {
            return parent::render($request, $exception);
        }

        // Exception wants to return its own response
        if ($exception instanceof Responsable) {
            return $exception->toResponse($request);
        }

        // Convert to public-friendly exception
        $exception = $this->prepareException($this->mapException($exception));

        // Custom handlers
        if ($response = $this->renderViaCallbacks($request, $exception)) {
            return $response;
        }

        // Exception is a response
        if ($exception instanceof HttpResponseException) {
            return $exception->getResponse();
        }

        /**
         * @event exception.beforeRender
         * Fires as the exception renders and returns an optional custom response.
         *
         * Example usage
         *
         *     Event::listen('exception.beforeRender', function (\Exception $exception) {
         *         return 'An error happened!';
         *     });
         */
        $statusCode = $this->getStatusCode($exception);
        if (($event = Event::fire('exception.beforeRender', [$exception, $statusCode, $request], true)) !== null) {
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
        elseif ($exception instanceof NotFoundHttpException) {
            $code = 404;
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
     * @deprecated use renderable
     */
    public function error(callable $callback)
    {
        $this->renderable($callback);
    }

    /**
     * renderViaCallbacks tries to render a response from request and exception via render callbacks.
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function renderViaCallbacks($request, Throwable $e)
    {
        foreach ($this->renderCallbacks as $renderCallback) {
            foreach ($this->firstClosureParameterTypes($renderCallback) as $type) {
                if (!is_a($e, $type)) {
                    continue;
                }

                $response = $renderCallback($e, $request);
                if (!$response) {
                    continue;
                }

                if (is_string($response)) {
                    return Response::make($response);
                }

                return $response;
            }
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
