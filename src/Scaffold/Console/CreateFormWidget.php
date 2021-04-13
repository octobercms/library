<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateFormWidget extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:formwidget';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new form widget.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'FormWidget';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'formwidget/formwidget.stub'      => 'formwidgets/{{studly_name}}.php',
        'formwidget/partial.stub'         => 'formwidgets/{{lower_name}}/partials/_{{lower_name}}.htm',
        'formwidget/stylesheet.stub'      => 'formwidgets/{{lower_name}}/assets/css/{{lower_name}}.css',
        'formwidget/javascript.stub'      => 'formwidgets/{{lower_name}}/assets/js/{{lower_name}}.js',
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
            ['widget', InputArgument::REQUIRED, 'The name of the form widget. Eg: PostList'],
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
