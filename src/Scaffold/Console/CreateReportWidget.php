<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateReportWidget
 */
class CreateReportWidget extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:reportwidget
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the report widget. Eg: TopPages}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new report widget.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Report Widget';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('reportwidget/reportwidget.stub', 'reportwidgets/{{studly_name}}.php');
        $this->makeStub('reportwidget/widget.stub', 'reportwidgets/{{lower_name}}/partials/_{{lower_name}}.php');
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
