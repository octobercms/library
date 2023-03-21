<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateJob
 */
class CreateJob extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:job
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the job class to generate. <info>(eg: ImportPosts)</info>}
        {--s|sync : Indicates that job should be synchronous}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new job class.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Job';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        if ($this->option('sync')) {
            $this->makeStub('job/job.stub', 'jobs/{{studly_name}}.php');
        }
        else {
            $this->makeStub('job/job.queued.stub', 'jobs/{{studly_name}}.php');
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
