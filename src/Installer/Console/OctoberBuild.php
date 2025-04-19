<?php

namespace October\Rain\Installer\Console;

use Lang;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\Command;

/**
 * OctoberBuild installs the necessary core packages
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class OctoberBuild extends Command
{
    use \October\Rain\Installer\Traits\SetupHelper;
    use \October\Rain\Installer\Traits\SetupBuilder;

    /**
     * The console command name.
     */
    protected $name = 'october:build';

    /**
     * The console command description.
     */
    protected $description = 'Installs the necessary core packages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Installing Dependencies
        $this->output->section(Lang::get('system::lang.installer.dependencies_section'));

        $this->setupInstallOctober();

        $this->outputOutro();
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['want', 'w', InputOption::VALUE_REQUIRED, 'Provide a custom version.'],
        ];
    }
}
