<?php namespace October\Rain\Foundation;

use October\Rain\Support\Str;
use October\Rain\Events\EventServiceProvider;
use October\Rain\Router\RoutingServiceProvider;
use October\Rain\Foundation\Providers\LogServiceProvider;
use October\Rain\Foundation\Providers\MakerServiceProvider;
use October\Rain\Foundation\Providers\ExecutionContextProvider;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Illuminate\Foundation\Application as ApplicationBase;
use Illuminate\Foundation\AliasLoader;
use Carbon\Laravel\ServiceProvider as CarbonServiceProvider;
use Illuminate\Support\Env;
use Throwable;
use Closure;

/**
 * Application
 */
class Application extends ApplicationBase
{
    /**
     * @var string pluginsPath is the base path for plugins
     */
    protected $pluginsPath;

    /**
     * @var string themesPath is the base path for themes
     */
    protected $themesPath;

    /**
     * @var string cachePath is the base path for cache files
     */
    protected $cachePath;

    /**
     * publicPath gets the path to the public / web directory
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath;
    }

    /**
     * langPath gets the path to the language files
     * @return string
     */
    public function langPath()
    {
        return $this->basePath.'/lang';
    }

    /**
     * registerBaseServiceProviders registers all of the base service providers
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new LogServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));

        $this->register(new MakerServiceProvider($this));

        $this->register(new ExecutionContextProvider($this));

        $this->register(new CarbonServiceProvider($this));
    }

    /**
     * bindPathsInContainer binds all of the application paths in the container
     */
    protected function bindPathsInContainer()
    {
        parent::bindPathsInContainer();

        $this->instance('path.plugins', $this->pluginsPath());
        $this->instance('path.themes', $this->themesPath());
        $this->instance('path.cache', $this->cachePath());
        $this->instance('path.temp', $this->tempPath());
    }

    /**
     * storagePath returns the path to the storage directory
     */
    public function storagePath(): string
    {
        return $this->storagePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage';
    }

    /**
     * setStoragePath sets path path for cache files
     */
    public function setStoragePath(string $path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * cachePath return path for cache files
     */
    public function cachePath(): string
    {
        return $this->cachePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage';
    }

    /**
     * setCachePath sets path path for cache files
     */
    public function setCachePath(string $path)
    {
        $this->cachePath = $path;

        $this->instance('path.cache', $path);

        $this->instance('path.temp', $path.DIRECTORY_SEPARATOR.'temp');

        return $this;
    }

    /**
     * pluginsPath returns path to location of themes
     */
    public function pluginsPath(): string
    {
        return $this->pluginsPath ?: $this->basePath.DIRECTORY_SEPARATOR.'plugins';
    }

    /**
     * setPluginsPath sets path to location of plugins
     */
    public function setPluginsPath(string $path)
    {
        $this->pluginsPath = $path;

        $this->instance('path.plugins', $path);

        return $this;
    }

    /**
     * themesPath returns path to location of themes
     */
    public function themesPath(): string
    {
        return $this->themesPath ?: $this->basePath.DIRECTORY_SEPARATOR.'themes';
    }

    /**
     * setThemesPath sets path to location of themes
     */
    public function setThemesPath($path)
    {
        $this->themesPath = $path;

        $this->instance('path.themes', $path);

        return $this;
    }

    /**
     * tempPath returns path for storing temporary files.
     */
    public function tempPath(): string
    {
        return $this->cachePath().DIRECTORY_SEPARATOR.'temp';
    }

    /**
     * Normalize a relative or absolute path to a cache file.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->cachePath().DIRECTORY_SEPARATOR.$default;
        }

        return Str::startsWith($env, '/')
            ? $env
            : $this->basePath($env);
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
        return $this['execution.context'] === 'back-end';
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
            'log'                  => [\Illuminate\Log\Logger::class, \Psr\Log\LoggerInterface::class],
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
            'url'                  => [\Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * registerClassAlias registers a new global alias, useful for facades
     */
    public function registerClassAlias(string $key, string $class)
    {
        AliasLoader::getInstance()->alias($key, $class);
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
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'framework/config.php');
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return $this->normalizeCachePath('APP_ROUTES_CACHE', 'framework/routes.php');
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return $this->normalizeCachePath('APP_COMPILED_CACHE', 'framework/compiled.php');
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'framework/services.php');
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath()
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'framework/packages.php');
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedClassesPath()
    {
        return $this->normalizeCachePath('APP_CLASSES_CACHE', 'framework/classes.php');
    }
}
