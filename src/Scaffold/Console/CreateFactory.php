<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateFactory
 */
class CreateFactory extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:factory
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the factory class to generate. <info>(eg: PostFactory)</info>}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new factory class.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Factory';

    /**
     * handle executes the console command
     */
    public function handle()
    {
        if (!ends_with($this->argument('name'), 'Factory')) {
            $this->components->error('Factory classes names must end in "Factory"');
            return;
        }

        parent::handle();
    }

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        if ($this->isAppNamespace()) {
            $this->makeStub('factory/factory_app.stub', 'database/factories/{{studly_name}}.php');
        }
        else {
            $this->makeStub('factory/factory.stub', 'updates/factories/{{studly_name}}.php');
        }
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
