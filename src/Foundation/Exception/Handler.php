<?php namespace October\Rain\Foundation\Exception;

use Event;
use Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\Router;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Exception\NotFoundException;
use October\Rain\Exception\AjaxException;
use Throwable;

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

        parent::report($exception);

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $exception)
    {
        // Exception occurred before system has booted
        if (!$this->hasBootedEvents()) {
            return parent::render($request, $exception);
        }

        $exception = $this->mapException($exception);

        // Exception has a render method (Laravel 12)
        if (method_exists($exception, 'render') && $response = $exception->render($request)) {
            return $this->finalizeRenderedResponse(
                $request,
                Router::toResponse($request, $response),
                $exception
            );
        }

        // Exception wants to return its own response
        if ($exception instanceof Responsable) {
            return $this->finalizeRenderedResponse($request, $exception->toResponse($request), $exception);
        }

        // Convert to public-friendly exception
        $exception = $this->prepareException($exception);

        // Custom handlers
        if ($response = $this->renderViaCallbacks($request, $exception)) {
            return $this->finalizeRenderedResponse($request, $response, $exception);
        }

        // Exception is a response
        if ($exception instanceof HttpResponseException) {
            return $this->finalizeRenderedResponse($request, $exception->getResponse(), $exception);
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
            return $this->finalizeRenderedResponse(
                $request,
                Response::make($event, $statusCode),
                $exception
            );
        }

        // Standard Laravel 12 rendering
        return $this->finalizeRenderedResponse($request, match (true) {
            $exception instanceof AuthenticationException => $this->unauthenticated($request, $exception),
            $exception instanceof ValidationException => $this->convertValidationExceptionToResponse($exception, $request),
            default => $this->renderExceptionResponse($request, $exception),
        }, $exception);
    }

    /**
     * prepareException for rendering.
     *
     * @param  \Throwable  $e
     * @return \Throwable
     */
    protected function prepareException(Throwable $e): Throwable
    {
        return match (true) {
            // October-specific: NotFoundException → NotFoundHttpException
            $e instanceof NotFoundException => new NotFoundHttpException($e->getMessage(), $e),

            // Laravel 12 standard conversions
            $e instanceof BackedEnumCaseNotFoundException => new NotFoundHttpException($e->getMessage(), $e),
            $e instanceof ModelNotFoundException => new NotFoundHttpException($e->getMessage(), $e),
            $e instanceof AuthorizationException && $e->hasStatus() => new HttpException(
                $e->status(), $e->response()?->message() ?: (HttpResponse::$statusTexts[$e->status()] ?? 'Whoops, looks like something went wrong.'), $e
            ),
            $e instanceof AuthorizationException && ! $e->hasStatus() => new AccessDeniedHttpException($e->getMessage(), $e),
            $e instanceof TokenMismatchException => new HttpException(419, $e->getMessage(), $e),
            $e instanceof RequestExceptionInterface => new BadRequestHttpException('Bad request.', $e),
            $e instanceof RecordNotFoundException => new NotFoundHttpException('Not found.', $e),
            $e instanceof RecordsNotFoundException => new NotFoundHttpException('Not found.', $e),
            default => $e,
        };
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
