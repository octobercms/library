<?php namespace October\Rain\Scaffold\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Scaffold\Templates\FormWidget;

class CreateFormWidget extends Command
{

    /**
     * The console command name.
     */
    protected $name = 'create:formwidget';

    /**
     * The console command description.
     */
    protected $description = 'Creates a new form widget.';

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

        $destinationPath = plugins_path() . '/' . strtolower($authorName) . '/' . strtolower($pluginName);
        $widgetName = $this->argument('widgetName');
        $vars = [
            'name' => $widgetName,
            'author' => $authorName,
            'plugin' => $pluginName
        ];

        FormWidget::make($destinationPath, $vars, $this->option('force'));

        $this->info(sprintf('Successfully generated Form Widget named "%s"', $widgetName));
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments()
    {
        return [
            ['pluginCode', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
            ['widgetName', InputArgument::REQUIRED, 'The name of the form widget. Eg: PostList'],
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