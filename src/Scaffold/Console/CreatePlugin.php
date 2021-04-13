<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreatePlugin extends GeneratorCommand
{
    /**
     * @var string name of console command
     */
    protected $name = 'create:plugin';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new plugin.';

    /**
     * @var string type of class being generated
     */
    protected $type = 'Plugin';

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [
        'plugin/plugin.stub'   => 'Plugin.php',
        'plugin/version.stub'  => 'updates/version.yaml',
        'plugin/composer.stub' => 'composer.json',
    ];

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        /*
         * Extract the author and name from the plugin code
         */
        $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);

        if (count($parts) !== 2) {
            $this->error('Invalid plugin name, either too many dots or not enough.');
            $this->error('Example name: AuthorName.PluginName');
            return [];
        }


        $pluginName = array_pop($parts);
        $authorName = array_pop($parts);

        return [
            'name'   => $pluginName,
            'author' => $authorName,
        ];
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
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
