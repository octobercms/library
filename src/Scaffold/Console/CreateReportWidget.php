<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * CreateReportWidget
 */
class CreateReportWidget extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:reportwidget';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new report widget.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'ReportWidget';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'reportwidget/reportwidget.stub' => 'reportwidgets/{{studly_name}}.php',
        'reportwidget/widget.stub'       => 'reportwidgets/{{lower_name}}/partials/_{{lower_name}}.php',
    ];

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars()
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $widget = $this->argument('widget');

        return [
            'name' => $widget,
            'author' => $author,
            'plugin' => $plugin
        ];
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Google'],
            ['widget', InputArgument::REQUIRED, 'The name of the report widget. Eg: TopPages'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
