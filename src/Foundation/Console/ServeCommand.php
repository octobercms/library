<?php namespace October\Rain\Foundation\Console;

use Illuminate\Foundation\Console\ServeCommand as ServeCommandParent;
use Symfony\Component\Process\PhpExecutableFinder;

class ServeCommand extends ServeCommandParent
{
    /**
     * handle the console command.
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        if (file_exists(base_path('public'))) {
            chdir(base_path('public'));
        }

        $this->line("<info>October CMS development server started:</info> http://{$this->host()}:{$this->port()}");

        $environmentFile = $this->option('env')
            ? base_path('.env').'.'.$this->option('env')
            : base_path('.env');

        $hasEnvironment = file_exists($environmentFile);

        $environmentLastModified = $hasEnvironment
            ? filemtime($environmentFile)
            : now()->addDays(30)->getTimestamp();

        $process = $this->startProcess($hasEnvironment);

        while ($process->isRunning()) {
            if ($hasEnvironment) {
                clearstatcache(false, $environmentFile);
            }

            if (! $this->option('no-reload') &&
                $hasEnvironment &&
                filemtime($environmentFile) > $environmentLastModified) {
                $environmentLastModified = filemtime($environmentFile);

                $this->comment('Environment modified. Restarting server...');

                $process->stop(5);

                $process = $this->startProcess($hasEnvironment);
            }

            usleep(500 * 1000);
        }

        $status = $process->getExitCode();

        if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
        }

        return $status;
    }

    /**
     * serverCommand gets the full server command.
     * @return array
     */
    protected function serverCommand()
    {
        $server = file_exists(base_path('server.php'))
            ? base_path('server.php')
            : __DIR__.'/../resources/server.php';

        return [
            (new PhpExecutableFinder)->find(false),
            '-S',
            $this->host().':'.$this->port(),
            $server,
        ];
    }
}
