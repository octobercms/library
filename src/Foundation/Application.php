<?php namespace October\Rain\Foundation;

use Closure;
use Illuminate\Foundation\Application as ApplicationBase;
use Symfony\Component\Debug\Exception\FatalErrorException;
use October\Rain\Events\EventServiceProvider;
use October\Rain\Router\RoutingServiceProvider;
use October\Rain\Foundation\Providers\LogServiceProvider;
use October\Rain\Foundation\Providers\MakerServiceProvider;
use October\Rain\Foundation\Providers\ExecutionContextProvider;
use Throwable;
use Exception;

class Application extends ApplicationBase
{
    /**
     * The base path for plugins.
     *
     * @var string
     */
    protected $pluginsPath;

    /**
     * The base path for themes.
     *
     * @var string
     */
    protected $themesPath;

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath;
    }

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function langPath()
    {
        return $this->basePath.'/lang';
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new LogServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));

        $this->register(new MakerServiceProvider($this));

        $this->register(new ExecutionContextProvider($this));
    }

    /**
     * Run the given array of bootstrap classes.
     *
     * @param  array  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        $exceptions = [];
        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->fire('bootstrapping: '.$bootstrapper, [$this]);

            // Defer any exceptions until after the application has been
            // bootstrapped so that the exception handler can run without issues
            try {
                $this->make($bootstrapper)->bootstrap($this);
            } catch (\Exception $ex) {
                $exceptions[] = $ex;
            }

            $this['events']->fire('bootstrapped: '.$bootstrapper, [$this]);
        }

        if (!empty($exceptions)) {
            throw $exceptions[0];
        }
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        parent::bindPathsInContainer();

        $this->instance('path.plugins', $this->pluginsPath());
        $this->instance('path.themes', $this->themesPath());
        $this->instance('path.temp', $this->tempPath());
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function pluginsPath()
    {
        return $this->pluginsPath ?: $this->basePath.'/plugins';
    }

    /**
     * Set the plugins path for the application.
     *
     * @param  string $path
     * @return $this
     */
    public function setPluginsPath($path)
    {
        $this->pluginsPath = $path;
        $this->instance('path.plugins', $path);
        return $this;
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function themesPath()
    {
        return $this->themesPath ?: $this->basePath.'/themes';
    }

    /**
     * Set the themes path for the application.
     *
     * @param  string $path
     * @return $this
     */
    public function setThemesPath($path)
    {
        $this->themesPath = $path;
        $this->instance('path.themes', $path);
        return $this;
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function tempPath()
    {
        return $this->basePath.'/storage/temp';
    }

    /**
     * Resolve the given type from the container.
     *
     * (Overriding Container::make)
     *
     * @param  string  $abstract
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        if ($parameters) {
            return $this->make(Maker::class)->make($abstract, $parameters);
        }

        return parent::make($abstract);
    }

    /**
     * Register a "before" application filter.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public function before($callback)
    {
        return $this['router']->before($callback);
    }

    /**
     * Register an "after" application filter.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public function after($callback)
    {
        return $this['router']->after($callback);
    }

    /**
     * Register an application error handler.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        $this->make('Illuminate\Contracts\Debug\ExceptionHandler')->error($callback);
    }

    /**
     * Register an error handler for fatal errors.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function fatal(Closure $callback)
    {
        $this->error(function (FatalErrorException $e) use ($callback) {
            return call_user_func($callback, $e);
        });
    }

    /**
     * Determine if we are running in the back-end area.
     *
     * @return bool
     */
    public function runningInBackend()
    {
        return $this['execution.context'] == 'back-end';
    }

    /**
     * Returns true if a database connection is present.
     * @return boolean
     */
    public function hasDatabase()
    {
        try {
            $this['db.connection']->getPdo();
        }
        catch (Throwable $ex) {
            return false;
        }

        return true;
    }

    /**
     * Set the current application locale.
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        parent::setLocale($locale);

        $this['events']->fire('locale.changed', [$locale]);
    }

    //
    // Core aliases
    //

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        $aliases = [
            'app'                  => [\October\Rain\Foundation\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class],
            'blade.compiler'       => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache'                => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store'          => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class],
            'config'               => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'cookie'               => [\Illuminate\Cookie\CookieJar::class, \Illuminate\Contracts\Cookie\Factory::class, \Illuminate\Contracts\Cookie\QueueingFactory::class],
            'encrypter'            => [\Illuminate\Encryption\Encrypter::class, \Illuminate\Contracts\Encryption\Encrypter::class],
            'db'                   => [\October\Rain\Database\DatabaseManager::class],
            'db.connection'        => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'events'               => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files'                => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem'           => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk'      => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud'     => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'hash'                 => [\Illuminate\Contracts\Hashing\Hasher::class],
            'translator'           => [\Illuminate\Translation\Translator::class, \Illuminate\Contracts\Translation\Translator::class],
            'log'                  => [\Illuminate\Log\Writer::class, \Illuminate\Contracts\Logging\Log::class, \Psr\Log\LoggerInterface::class],
            'mailer'               => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'queue'                => [\Illuminate\Queue\QueueManager::class, \Illuminate\Contracts\Queue\Factory::class, \Illuminate\Contracts\Queue\Monitor::class],
            'queue.connection'     => [\Illuminate\Contracts\Queue\Queue::class],
            'queue.failer'         => [\Illuminate\Queue\Failed\FailedJobProviderInterface::class],
            'redirect'             => [\Illuminate\Routing\Redirector::class],
            'redis'                => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
            'request'              => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            'router'               => [\Illuminate\Routing\Router::class, \Illuminate\Contracts\Routing\Registrar::class, \Illuminate\Contracts\Routing\BindingRegistrar::class],
            'session'              => [\Illuminate\Session\SessionManager::class],
            'session.store'        => [\Illuminate\Session\Store::class, \Illuminate\Contracts\Session\Session::class],
            'url'                  => [\October\Rain\Router\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    //
    // Caching
    //

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this['path.storage'].'/framework/config.php';
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return $this['path.storage'].'/framework/routes.php';
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return $this->storagePath().'/framework/compiled.php';
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->storagePath().'/framework/services.php';
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath()
    {
        return $this->storagePath().'/framework/packages.php';
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedClassesPath()
    {
        return $this->storagePath().'/framework/classes.php';
    }
}
