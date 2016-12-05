<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateFormWidget extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:formwidget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new form widget.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'FormWidget';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'formwidget/formwidget.stub'      => 'formwidgets/{{studly_name}}.php',
        'formwidget/partial.stub'         => 'formwidgets/{{lower_name}}/partials/_{{lower_name}}.htm',
        'formwidget/stylesheet.stub'      => 'formwidgets/{{lower_name}}/assets/css/{{lower_name}}.css',
        'formwidget/javascript.stub'      => 'formwidgets/{{lower_name}}/assets/js/{{lower_name}}.js',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
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
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: RainLab.Blog'],
            ['widget', InputArgument::REQUIRED, 'The name of the form widget. Eg: PostList'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
