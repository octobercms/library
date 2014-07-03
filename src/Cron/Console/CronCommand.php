<?php namespace October\Rain\Cron\Console;

use Illuminate\Console\Command;
use October\Rain\Cron\Models\Job;
use October\Rain\Cron\CronJob;

class CronCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Executes the latest job in the cron queue";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($job = Job::isAvailable()->first()) {
            $cronJob = new CronJob($this->laravel, $job);
            $cronJob->fire();
        }
    }

}
