<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateComponent extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:component';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new plugin component.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'Component';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'component/component.stub'  => 'components/{{studly_name}}.php',
        'component/default.stub' => 'components/{{lower_name}}/default.htm',
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
        $component = $this->argument('component');

        return [
            'name' => $component,
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['component', InputArgument::REQUIRED, 'The name of the component. Eg: Posts'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.']
        ];
    }
}
