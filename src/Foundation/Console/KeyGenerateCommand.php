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

        // Next, we will replace the application key in the config file so it is
        // automatically setup for this developer. This key gets generated using a
        // secure random byte generator and is later base64 encoded for storage.
        if (!$this->setKeyInConfigFile($key)) {
            return;
        }

        $this->laravel['config']['app.key'] = $key;

        $this->info("Application key [$key] set successfully.");
    }

    /**
     * Set the application key in the config file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInConfigFile($key)
    {
        if (!$this->confirmToProceed()) {
            return false;
        }

        $currentKey = $this->laravel['config']['app.key'];

        list($path, $contents) = $this->getKeyFile();

        $contents = str_replace($currentKey, $key, $contents);

        $this->files->put($path, $contents);

        return true;
    }

    /**
     * Get the key file and contents.
     *
     * @return array
     */
    protected function getKeyFile()
    {
        $env = $this->option('env') ? $this->option('env').'/' : '';

        $contents = $this->files->get($path = $this->laravel['path.config']."/{$env}app.php");

        return [$path, $contents];
    }
}
