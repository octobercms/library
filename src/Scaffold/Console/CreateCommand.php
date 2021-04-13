<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateCommand extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:command';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new console command.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'Command';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'command/command.stub' => 'console/{{studly_name}}.php',
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
        $command = $this->argument('command-name');

        return [
            'name' => $command,
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
            ['command-name', InputArgument::REQUIRED, 'The name of the command. Eg: MyCommand'],
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
