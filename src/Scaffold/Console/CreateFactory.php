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
        {name : The name of the job class to generate. <info>(eg: PostFactory)</info>}
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
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('factory/factory.stub', 'factories/{{studly_name}}.php');
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
