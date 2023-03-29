<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateFormWidget
 */
class CreateFormWidget extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:formwidget
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the form widget. Eg: PostList}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new form widget.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Form Widget';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('formwidget/formwidget.stub', 'formwidgets/{{studly_name}}.php');
        $this->makeStub('formwidget/partial.stub', 'formwidgets/{{lower_name}}/partials/_{{lower_name}}.php');
        $this->makeStub('formwidget/stylesheet.stub', 'formwidgets/{{lower_name}}/assets/css/{{lower_name}}.css');
        $this->makeStub('formwidget/javascript.stub', 'formwidgets/{{lower_name}}/assets/js/{{lower_name}}.js');
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
