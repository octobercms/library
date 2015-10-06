<?php namespace October\Rain\Scaffold\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Scaffold\Templates\Plugin;

class CreatePlugin extends Command
{

    /**
     * The console command name.
     */
    protected $name = 'create:plugin';

    /**
     * The console command description.
     */
    protected $description = 'Creates a new plugin.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function fire()
    {
        /*
         * Extract the author and name from the plugin code
         */
        $pluginCode = $this->argument('pluginCode');
        $parts = explode('.', $pluginCode);

        if (count($parts) != 2) {
            $this->error('Invalid plugin name, either too many dots or not enough.');
            $this->error('Example name: AuthorName.PluginName');
            return;
        }


        $pluginName = array_pop($parts);
        $authorName = array_pop($parts);

        $destinationPath = base_path() . '/plugins';
        $vars = [
            'name'   => $pluginName,
            'author' => $authorName,
        ];

        Plugin::make($destinationPath, $vars, $this->option('force'));

        $this->info(sprintf('Successfully generated Plugin named "%s"', $pluginCode));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['pluginCode', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }

}