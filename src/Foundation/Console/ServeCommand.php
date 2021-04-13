<?php namespace October\Rain\Foundation\Console;

use Illuminate\Foundation\Console\ServeCommand as ServeCommandParent;

class ServeCommand extends ServeCommandParent
{
    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        if (file_exists(base_path('public'))) {
            chdir(base_path('public'));
        }

        $this->line("<info>October CMS development server started:</info> http://{$this->host()}:{$this->port()}");

        passthru($this->serverCommand(), $status);

        if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
        }

        return $status;
    }
}
