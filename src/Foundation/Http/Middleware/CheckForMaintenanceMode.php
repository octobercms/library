<?php

namespace October\Rain\Foundation\Http\Middleware;

use Lang;
use View;
use Closure;
use Response;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode as Middleware;

class CheckForMaintenanceMode extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (\Illuminate\Foundation\Http\Exceptions\MaintenanceModeException $ex) {
            // Smoothly handle AJAX requests
            if (request()->ajax()) {
                return Response::make(Lang::get('system::lang.page.maintenance.help') . "\r\n" . $ex->getMessage(), 503);
            }

            // Check if there is a project level view to override the system one
            View::addNamespace('base', base_path());
            if (View::exists('base::maintenance')) {
                $view = 'base::maintenance';
            } else {
                $view = 'system::maintenance';
            }

            return Response::make(View::make($view, [
                'message'           => $ex->getMessage(),
                'wentDownAt'        => $ex->wentDownAt,
                'retryAfter'        => $ex->retryAfter,
                'willBeAvailableAt' => $ex->willBeAvailableAt,
            ]), 503);
        }
    }
}
