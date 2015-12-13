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
    public function fire()
    {
        list($path, $contents) = $this->getKeyFile();

        $oldKey = $this->laravel['config']['app.key'];

        parent::fire();

        $newKey = $this->laravel['config']['app.key'];

        $contents = str_replace($oldKey, $newKey, $contents);

        $this->files->put($path, $contents);
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
