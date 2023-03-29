<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateComponent
 */
class CreateComponent extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:component
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the component. Eg: Posts}
        {--o|overwrite : Overwrite existing files with generated ones}';

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
    protected $typeLabel = 'Component';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('component/component.stub', 'components/{{studly_name}}.php');
        $this->makeStub('component/default.stub', 'components/{{lower_name}}/default.htm');
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
