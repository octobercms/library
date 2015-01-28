<?php namespace October\Rain\Scaffold\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Support\Str;
use October\Rain\Scaffold\Templates\Controller;

class CreateController extends Command
{

    /**
     * The console command name.
     */
    protected $name = 'create:controller';

    /**
     * The console command description.
     */
    protected $description = 'Creates a new controller.';

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
        $controllerName = $this->argument('controllerName');

        /*
         * Determine the model name to use,
         * either supplied or singular from the controller name.
         */
        $modelName = $this->option('model');
        if (!$modelName)
            $modelName = Str::singular($controllerName);

        $vars = [
            'name' => $controllerName,
            'model' => $modelName,
            'author' => $authorName,
            'plugin' => $pluginName
        ];

        Controller::make($destinationPath, $vars, $this->option('force'));

        $this->info(sprintf('Successfully generated Controller and views for "%s"', $controllerName));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['pluginCode', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['controllerName', InputArgument::REQUIRED, 'The name of the controller. Eg: Posts'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Define which model name to use, otherwise the singular controller name is used.'],
        ];
    }

}