<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateContentField
 */
class CreateContentField extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:contentfield
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the content field. Eg: IconPicker}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new content field.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Content Field';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('contentfield/contentfield.stub', 'contentfields/{{studly_name}}.php');
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
