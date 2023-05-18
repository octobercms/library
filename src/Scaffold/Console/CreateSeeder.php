<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateSeeder
 */
class CreateSeeder extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:seeder
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the job class to generate. <info>(eg: PostSeeder)</info>}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new seeder class.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Seeder';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('seeders/seeder.stub', 'seeder/{{studly_name}}.php');
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
