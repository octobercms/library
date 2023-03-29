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
    protected $signature = 'create:model
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the model. Eg: Post}
        {--soft-deletes : Implement soft deletion on this model}
        {--no-timestamps : Disable auto-timestamps on this model}
        {--no-migration : Do not generate a migration file for this model}
        {--o|overwrite : Overwrite existing files with generated ones}';

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

        if (!$this->option('no-migration')) {
            $this->call('create:migration', array_filter([
                'name' => 'Create'.$this->vars['studly_plural_name'].'Table',
                'namespace' => $this->argument('namespace'),
                '--create' => $this->vars['namespace_table'].'_'.$this->vars['snake_plural_name'],
                '--soft-deletes' => $this->option('soft-deletes'),
                '--no-timestamps' => $this->option('no-timestamps'),
                '--overwrite' => $this->option('overwrite')
            ]));
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
            'softDeletes' => $this->option('soft-deletes'),
            'timestamps' => !$this->option('no-timestamps')
        ];
    }
}
