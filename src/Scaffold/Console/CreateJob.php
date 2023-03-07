<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

class CreateJob extends BaseScaffoldCommand
{
    /**
     * @var string|null The default command name for lazy loading.
     */
    protected static $defaultName = 'create:job';

    /**
     * @var string The name and signature of this command.
     */
    protected $signature = 'create:job
        {plugin : The name of the plugin. <info>(eg: Acme.Blog)</info>}
        {name : The name of the job class to generate. <info>(eg: ImportPosts)</info>}
        {--s|sync : Overwrite existing files with generated files.}
        {--f|force : Overwrite existing files with generated files.}';

    /**
     * @var string The console command description.
     */
    protected $description = 'Creates a new job class.';

    /**
     * @var string The type of class being generated.
     */
    protected $type = 'Job';

    /**
     * @var array A mapping of stubs to generated files.
     */
    protected $stubs = [
        'scaffold/job/job.queued.stub' => 'jobs/{{studly_name}}.php',
    ];

    /**
     * @inheritDoc
     */
    public function prepareVars(): array
    {
        if ($this->option('sync')) {
            $this->stubs['scaffold/job/job.stub'] = 'jobs/{{studly_name}}.php';
        }

        return parent::prepareVars();
    }
}
