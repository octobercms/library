<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateFilterWidget extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:filterwidget';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new filter widget.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'FilterWidget';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'filterwidget/filterwidget.stub' => 'filterwidgets/{{studly_name}}.php',
        'filterwidget/partial.stub'      => 'filterwidgets/{{lower_name}}/partials/_{{lower_name}}.php',
        'filterwidget/partial_form.stub' => 'filterwidgets/{{lower_name}}/partials/_{{lower_name}}_form.php',
        'filterwidget/stylesheet.stub'   => 'filterwidgets/{{lower_name}}/assets/css/{{lower_name}}.css',
        'filterwidget/javascript.stub'   => 'filterwidgets/{{lower_name}}/assets/js/{{lower_name}}.js',
    ];

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.User'],
            ['widget', InputArgument::REQUIRED, 'The name of the filter widget. Eg: Discount'],
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
