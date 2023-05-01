<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateTest
 */
class CreateTest extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:test
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the test class to generate. <info>(eg: UserTest)</info>}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new test class.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Test';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        if (!ends_with($this->argument('name'), 'Test')) {
            $this->components->error('Test classes names must end in "Test"');
            return;
        }

        parent::handle();
    }

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        if (!file_exists($this->getDestinationPath() . '/phpunit.xml')) {
            if ($this->isAppNamespace()) {
                $this->makeStub('test/phpunit.app.stub', 'phpunit.xml');
            }
            else {
                $this->makeStub('test/phpunit.plugin.stub', 'phpunit.xml');
            }
        }

        $this->makeStub('test/test.stub', 'tests/{{studly_name}}.php');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        return [
            'name' => $this->argument('name'),
            'namespace' => $this->argument('namespace'),
        ];
    }
}
