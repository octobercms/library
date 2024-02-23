<?php namespace October\Rain\Foundation;

use October\Rain\Support\Str;
use October\Rain\Support\Collection;
use October\Rain\Filesystem\Filesystem;
use October\Rain\Events\EventServiceProvider;
use October\Rain\Router\RoutingServiceProvider;
use October\Rain\Foundation\Providers\LogServiceProvider;
use October\Rain\Foundation\Providers\ExecutionContextProvider;
use Illuminate\Foundation\Application as ApplicationBase;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\ProviderRepository;
use Carbon\Laravel\ServiceProvider as CarbonServiceProvider;
use Illuminate\Support\Env;
use Throwable;
use Closure;
use Error;

/**
 * Application foundation class as an extension of Laravel
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
     * registerBaseServiceProviders registers all of the base service providers
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new LogServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));

        $this->register(new ExecutionContextProvider($this));

        $this->register(new CarbonServiceProvider($this));
    }

    /**
     * bindPathsInContainer binds all of the application paths in the container
     */
    protected function bindPathsInContainer()
    {
        parent::bindPathsInContainer();

        // Additional lang path check
        if (is_dir($directory = $this->path('lang'))) {
            $this->useLangPath($directory);
        }

        // October CMS paths
        $this->instance('path.plugins', $this->pluginsPath());
        $this->instance('path.themes', $this->themesPath());
        $this->instance('path.cache', $this->cachePath());
        $this->instance('path.temp', $this->tempPath());
    }

    /**
     * publicPath gets the path to the public / web directory
     * @return string
     */
    public function publicPath($path = '')
    {
        return $this->hasPublicFolder()
                ? $this->joinPaths($this->basePath('public'), $path)
                : $this->joinPaths($this->basePath, $path);
    }

    /**
     * hasPublicFolder returns true if a public folder exists, initiated by october:mirror
     */
    public function hasPublicFolder()
    {
        return file_exists($this->basePath('public'));
    }

    /**
     * cachePath return path for cache files
     * @param string $path
     * @return string
     */
    public function cachePath($path = '')
    {
        return $this->joinPaths($this->cachePath ?: $this->basePath('storage'), $path);
    }

    /**
     * useCachePath sets path path for cache files
     * @param string $path
     * @return $this
     */
    public function useCachePath($path)
    {
        $this->cachePath = $path;

        $this->instance('path.cache', $path);

        $this->instance('path.temp', $path.DIRECTORY_SEPARATOR.'temp');

        return $this;
    }

    /**
     * pluginsPath returns path to location of plugins
     * @param string $path
     * @return string
     */
    public function pluginsPath($path = '')
    {
        return $this->joinPaths($this->pluginsPath ?: $this->basePath('plugins'), $path);
    }

    /**
     * usePluginsPath sets path to location of plugins
     * @param string $path
     * @return $this
     */
    public function usePluginsPath($path)
    {
        $this->pluginsPath = $path;

        $this->instance('path.plugins', $path);

        return $this;
    }

    /**
     * themesPath returns path to location of themes
     * @param string $path
     * @return string
     */
    public function themesPath($path = '')
    {
        return $this->joinPaths($this->themesPath ?: $this->basePath('themes'), $path);
    }

    /**
     * useThemesPath sets path to location of themes
     * @param string $path
     * @return $this
     */
    public function useThemesPath($path)
    {
        $this->themesPath = $path;

        $this->instance('path.themes', $path);

        return $this;
    }

    /**
     * tempPath returns path for storing temporary files.
     * @param string $path
     * @return string
     */
    public function tempPath($path = ''): string
    {
        return $this->joinPaths($this->cachePath('temp'), $path);
    }

    /**
     * normalizeCachePath normalizes a relative or absolute path to a cache file.
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = Env::get($key))) {
            return $this->cachePath($default);
        }

        return Str::startsWith($env, '/')
            ? $env
            : $this->basePath($env);
    }

    /**
     * joinPaths together
     *
     * @todo Can be removed if Laravel >= 10
     *
     * @param  string  $basePath
     * @param  string  $path
     * @return string
     */
    public function joinPaths($basePath, $path = '')
    {
        return $basePath.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    /**
     * before logic is called before the router runs.
     * @param  \Closure|string  $callback
     * @return void
     */
    public function before($callback)
    {
        return $this['router']->before($callback);
    }

    /**
     * after logic is called after the router finishes.
     * @param  \Closure|string  $callback
     * @return void
     */
    public function after($callback)
    {
        return $this['router']->after($callback);
    }

    /**
     * error registers an application error handler.
     * @param  \Closure  $callback
     * @return void
     */
    public function error(callable $callback)
    {
        $this->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)->renderable($callback);
    }

    /**
     * @deprecated use App::error with an Error exception type
     */
    public function fatal(callable $callback)
    {
        $this->error(function(Error $e) use ($callback) {
            return $callback($e);
        });
    }

    /**
     * runningInBackend determines if we are running in the backend area.
     * @return bool
     */
    public function runningInBackend()
    {
        return $this['execution.context'] === 'backend';
    }

    /**
     * runningInFrontend determines if we are running in the frontend area.
     * @return bool
     */
    public function runningInFrontend()
    {
        return !$this->runningInBackend() && !$this->runningInConsole();
    }

    /**
     * hasDatabase returns true if a database connection is present.
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
     * setLocale for the application.
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        parent::setLocale($locale);

        $this['events']->dispatch('locale.changed', [$locale]);
    }

    //
    // Core registrations
    //

    /**
     * registerConfiguredProviders is entirely inherited from the parent,
     * except the October\Rain namespace is included in the partition.
     */
    public function registerConfiguredProviders()
    {
        $providers = Collection::make($this->config['app.providers'])
            ->partition(function ($provider) {
                return strpos($provider, 'Illuminate\\') === 0 ||
                    strpos($provider, 'October\\Rain\\') === 0;
            });

        $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

        (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
            ->load($providers->collapse()->toArray());
    }

    /**
     * registerCoreContainerAliases in the container.
     */
    public function registerCoreContainerAliases()
    {
        $aliases = [
            'app' => [\October\Rain\Foundation\Application::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class],
            'blade.compiler' => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache' => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store' => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class],
            'cache.psr6' => [\Symfony\Component\Cache\Adapter\Psr16Adapter::class, \Symfony\Component\Cache\Adapter\AdapterInterface::class, \Psr\Cache\CacheItemPoolInterface::class],
            'config' => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'cookie' => [\Illuminate\Cookie\CookieJar::class, \Illuminate\Contracts\Cookie\Factory::class, \Illuminate\Contracts\Cookie\QueueingFactory::class],
            'db' => [\October\Rain\Database\DatabaseManager::class],
            'db.connection' => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'db.schema' => [\Illuminate\Database\Schema\Builder::class],
            'encrypter' => [\Illuminate\Encryption\Encrypter::class, \Illuminate\Contracts\Encryption\Encrypter::class],
            'events' => [\October\Rain\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files' => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem' => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk' => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud' => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'hash' => [\Illuminate\Contracts\Hashing\Hasher::class],
            'hash.driver' => [\Illuminate\Contracts\Hashing\Hasher::class],
            'translator' => [\Illuminate\Translation\Translator::class, \Illuminate\Contracts\Translation\Translator::class],
            'log' => [\Illuminate\Log\Logger::class, \Psr\Log\LoggerInterface::class],
            'mail.manager' => [\Illuminate\Mail\MailManager::class, \Illuminate\Contracts\Mail\Factory::class],
            'mailer' => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'auth.password' => [\Illuminate\Auth\Passwords\PasswordBrokerManager::class, \Illuminate\Contracts\Auth\PasswordBrokerFactory::class],
            'auth.password.broker' => [\Illuminate\Auth\Passwords\PasswordBroker::class, \Illuminate\Contracts\Auth\PasswordBroker::class],
            'queue' => [\Illuminate\Queue\QueueManager::class, \Illuminate\Contracts\Queue\Factory::class, \Illuminate\Contracts\Queue\Monitor::class],
            'queue.connection' => [\Illuminate\Contracts\Queue\Queue::class],
            'queue.failer' => [\Illuminate\Queue\Failed\FailedJobProviderInterface::class],
            'redirect' => [\Illuminate\Routing\Redirector::class],
            'redis' => [\Illuminate\Redis\RedisManager::class, \Illuminate\Contracts\Redis\Factory::class],
            'redis.connection' => [\Illuminate\Redis\Connections\Connection::class, \Illuminate\Contracts\Redis\Connection::class],
            'request' => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            'router' => [\Illuminate\Routing\Router::class, \Illuminate\Contracts\Routing\Registrar::class, \Illuminate\Contracts\Routing\BindingRegistrar::class],
            'session' => [\Illuminate\Session\SessionManager::class],
            'session.store' => [\Illuminate\Session\Store::class, \Illuminate\Contracts\Session\Session::class],
            'url' => [\Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator' => [\October\Rain\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
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
    public function registerClassAlias(string $alias, string $class)
    {
        $this->registerClassAliases([$alias => $class]);
    }

    /**
     * registerClassAliases registers multiple global aliases, useful for renamed classes
     */
    public function registerClassAliases(array $aliases)
    {
        AliasLoader::getInstance($aliases);
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

    /**
     * getNamespace returns the application namespace.
     * @return string
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        return 'App\\';
    }

    /**
     * extendInstance is useful for extending singletons regardless of their execution
     */
    public function extendInstance($abstract, Closure $callback)
    {
        $this->afterResolving($abstract, $callback);

        if ($this->resolved($abstract)) {
            $callback($this->make($abstract), $this);
        }
    }
}
