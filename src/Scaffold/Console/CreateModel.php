<?php namespace October\Rain\Scaffold\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Scaffold\Templates\Model;

class CreateModel extends Command
{

    /**
     * The console command name.
     */
    protected $name = 'create:model';

    /**
     * The console command description.
     */
    protected $description = 'Creates a new model.';

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
        $modelName = $this->argument('modelName');
        $vars = [
            'name' => $modelName,
            'author' => $authorName,
            'plugin' => $pluginName
        ];

        Model::make($destinationPath, $vars, $this->option('force'));

        $this->info(sprintf('Successfully generated Model named "%s"', $modelName));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['pluginCode', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
            ['modelName', InputArgument::REQUIRED, 'The name of the model. Eg: Post'],
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