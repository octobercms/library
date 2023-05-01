<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

class CreatePlugin extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:plugin
        {namespace : The name of the plugin to create. <info>(eg: Acme.Blog)</info>}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new plugin.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Plugin';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('plugin/plugin.stub', 'Plugin.php');
        $this->makeStub('plugin/version.stub', 'updates/version.yaml');
        $this->makeStub('plugin/composer.stub', 'composer.json');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        if (!$this->validateInput()) {
            exit(1);
        }

        return [
            'namespace' => $this->argument('namespace'),
        ];
    }

    protected function validateInput()
    {
        if ($this->isAppNamespace()) {
            $this->error('Cannot create plugin in app namespace');
            return false;
        }

        // Extract the author and name from the plugin code
        $pluginCode = $this->argument('namespace');
        $parts = explode('.', $pluginCode);

        if (count($parts) !== 2) {
            $this->error('Invalid plugin name, either too many dots or not enough.');
            $this->error('Example name: AuthorName.PluginName');
            return false;
        }

        return true;
    }
}
