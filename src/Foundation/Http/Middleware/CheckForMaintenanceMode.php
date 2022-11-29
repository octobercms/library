<?php namespace October\Rain\Foundation\Http\Middleware;

use View;
use Closure;
use Response;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as CheckForMaintenanceModeBase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CheckForMaintenanceMode
 *
 * @package october\foundation
 * @author Alexey Bobkov, Samuel Georges
 */
class CheckForMaintenanceMode extends CheckForMaintenanceModeBase
{
    /**
     * handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        }
        catch (HttpException $ex) {
            $view = View::exists('app::maintenance') ? 'app::maintenance' : 'system::maintenance';
            $data = $this->app->maintenanceMode()->data();

            return Response::make(
                View::make($view, [
                    'message' => $ex->getMessage(),
                    'retryAfter' => $data['retry'] ?? null,
                ]),
                $ex->getStatusCode(),
                $ex->getHeaders()
            );
        }
    }
}
