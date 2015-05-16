<?php namespace October\Rain\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class HandleRedirects implements Middleware
{

    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new filter instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->app['config']->get('cms.enableRedirects', true)) {
            return $next($request);
        }

        if ($result = $this->redirectIfDuplicateSlash()) {
            return $this->app['redirect']->to($result, 301);
        }

        if ($result = $this->redirectToLinkPolicy()) {
            return $this->app['redirect']->to($result, 301);
        }

        return $next($request);
    }

    /**
     * Check if the request path ends in a single trailing slash.
     */
    protected function redirectIfDuplicateSlash()
    {
        $oldPath = $path = $this->app['request']->getPathInfo();

        // Remove duplicate slashes
        if (strpos($path, '//') !== false) {
            $path = preg_replace('~/{2,}~', '/', $path);
        }

        // Remove trailing slash
        if ($path != '/' && ends_with($path, '/')) {
            $path = substr($path, 0, -1);
        }

        return $oldPath != $path ? $path : null;
    }

    /**
     * Check if the schema matches the link policy defined in cms.linkPolicy.
     */
    protected function redirectToLinkPolicy()
    {
        $policy = strtolower($this->app['config']->get('cms.linkPolicy', 'detect'));
        $url = $this->app['request']->fullUrl();

        /*
         * Do nothing
         */
        if ($policy == 'detect') {
            return null;
        }

        /*
         * Redirect insecure to secure
         */
        if (
            $policy == 'secure' &&
            !$this->app['request']->isSecure() &&
            !starts_with($url, 'https://')
        ) {
            return preg_replace('~http://~', 'https://', $url, 1);
        }

        /*
         * Redirect secure to insecure
         */
        if (
            $policy == 'insecure' &&
            $this->app['request']->isSecure() &&
            !starts_with($url, 'http://')
        ) {
            return preg_replace('~https://~', 'http://', $url, 1);
        }

        return null;
    }

}
