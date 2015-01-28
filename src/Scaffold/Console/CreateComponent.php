<?php namespace October\Rain\Scaffold\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Support\Str;
use October\Rain\Scaffold\Templates\Component;

class CreateComponent extends Command
{

    /**
     * The console command name.
     */
    protected $name = 'create:component';

    /**
     * The console command description.
     */
    protected $description = 'Creates a new plugin component.';

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
        $pluginName = array_pop($parts);
        $authorName = array_pop($parts);

        $destinationPath = base_path() . '/plugins/' . strtolower($authorName) . '/' . strtolower($pluginName);
        $componentName = $this->argument('componentName');

        $vars = [
            'name' => $componentName,
            'author' => $authorName,
            'plugin' => $pluginName
        ];

        Component::make($destinationPath, $vars, $this->option('force'));

        $this->info(sprintf('Successfully generated Component for "%s"', $componentName));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['pluginCode', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['componentName', InputArgument::REQUIRED, 'The name of the component. Eg: Posts'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.']
        ];
    }

}