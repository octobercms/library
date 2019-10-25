<?php namespace October\Rain\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ClearCompiledCommand as ClearCompiledCommandBase;

class ClearCompiledCommand extends ClearCompiledCommandBase
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists($classesPath = $this->laravel->getCachedClassesPath())) {
            @unlink($classesPath);
        }

        parent::handle();
    }
}
