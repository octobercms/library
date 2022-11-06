<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateModel
 */
class CreateModel extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:model {namespace : App or Plugin Namespace (eg: RainLab.Blog)}
        {name : The name of the model. Eg: Post}
        {--force : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new model.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Model';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('model/model.stub', 'models/{{studly_name}}.php');
        $this->makeStub('model/fields.stub', 'models/{{lower_name}}/fields.yaml');
        $this->makeStub('model/columns.stub', 'models/{{lower_name}}/columns.yaml');
        $this->makeStub('model/create_table.stub', 'updates/create_{{snake_plural_name}}_table.php');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        return [
            'name' => $this->argument('name'),
            'namespace' => $this->argument('namespace')
        ];
    }
}
