<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateFilterWidget
 */
class CreateFilterWidget extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:filterwidget
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the filter widget. Eg: HasDiscount}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string name of console command
     */
    protected $name = 'create:filterwidget';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new filter widget.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Filter Widget';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('filterwidget/filterwidget.stub', 'filterwidgets/{{studly_name}}.php');
        $this->makeStub('filterwidget/partial.stub', 'filterwidgets/{{lower_name}}/partials/_{{lower_name}}.php');
        $this->makeStub('filterwidget/partial_form.stub', 'filterwidgets/{{lower_name}}/partials/_{{lower_name}}_form.php');
        $this->makeStub('filterwidget/stylesheet.stub', 'filterwidgets/{{lower_name}}/assets/css/{{lower_name}}.css');
        $this->makeStub('filterwidget/javascript.stub', 'filterwidgets/{{lower_name}}/assets/js/{{lower_name}}.js');
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
