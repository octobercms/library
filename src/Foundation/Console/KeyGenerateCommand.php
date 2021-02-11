<?php namespace October\Rain\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\KeyGenerateCommand as KeyGenerateCommandBase;

class KeyGenerateCommand extends KeyGenerateCommandBase
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'key:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Set the application key";

    /**
     * Create a new key generator command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            return $this->line('<comment>'.$key.'</comment>');
        }

        $currentKey = $this->laravel['config']['app.key'];

        if (!empty($currentKey) && (!$this->confirmToProceed())) {
            return;
        }

        // Next, we will replace the application key in the env file (if it exists) or config file
        // so it is automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (!$this->setKeyInEnvironmentFile($key)) {
            $this->setKeyInConfigFile($key);
        }

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Set the application key in the environment file.
     *
     * @param string $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key): bool
    {
        if (!$this->files->exists($this->laravel->environmentFilePath())) {
            return false;
        }

        $contents = $this->files->get($this->laravel->environmentFilePath());

        $matches = [];
        preg_match($this->keyReplacementPattern(), $contents, $matches);

        // Abort if the app key is not able to be written to the .env file.
        if (empty($matches)) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Set the application key in the config file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInConfigFile($key): bool
    {
        [$path, $contents] = $this->getKeyFile();

        $contents = preg_replace($this->configKeyReplacementPattern(), "$1{$key}$3", $contents);

        $this->files->put($path, $contents);

        return true;
    }

    /**
     * Get the key file and contents.
     *
     * @return array
     */
    protected function getKeyFile(): array
    {
        $env = $this->option('env') ? $this->option('env').'/' : $this->laravel->environment().'/';

        $path = $this->laravel['path.config']."/{$env}app.php";

        if ($this->files->exists($path)) {
            $config = include $path;

            if (!isset($config['key'])) {
                $env = '';
            }
        } else {
            $env = '';
        }

        $contents = $this->files->get($path = $this->laravel['path.config']."/{$env}app.php");

        return [$path, $contents];
    }

    /**
     * Get a regex pattern that will match config APP_KEY with any random key
     *
     * @return string
     */
    protected function configKeyReplacementPattern(): string
    {
        $escaped = preg_quote($this->laravel['config']['app.key'], '/');

        return "/(['\"]key['\"]\s*=>.*['\"])({$escaped})(['\"])/s";
    }
}
