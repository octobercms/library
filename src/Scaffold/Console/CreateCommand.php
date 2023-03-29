<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateCommand
 */
class CreateCommand extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:command
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the command. Eg: ProcessJobs}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new console command.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Command';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('command/command.stub', 'console/{{studly_name}}.php');
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
