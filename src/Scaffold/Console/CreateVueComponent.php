<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateVueComponent
 */
class CreateVueComponent extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:vuecomponent
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the Vue component. Eg: PostEditor}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new Vue component.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Vue Component';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('vuecomponent/vuecomponent.stub', 'vuecomponents/{{studly_name}}.php');
        $this->makeStub('vuecomponent/partial.stub', 'vuecomponents/{{lower_name}}/partials/_{{lower_name}}.php');
        $this->makeStub('vuecomponent/javascript.stub', 'vuecomponents/{{lower_name}}/assets/js/{{lower_name}}.js');
        $this->makeStub('vuecomponent/stylesheet.stub', 'vuecomponents/{{lower_name}}/assets/css/{{lower_name}}.css');
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