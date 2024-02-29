<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Scaffold\GeneratorCommandBase;
use October\Rain\Support\Str;

/**
 * CreateController
 */
class CreateController extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:controller
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the controller. Eg: Posts}
        {--model= : Define which model name to use, otherwise the singular controller name is used.}
        {--design= : Specify a design (basic, sidebar, survey, popup, custom)}
        {--no-form : Do not implement a form for this controller}
        {--no-list : Do not implement a list for this controller}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new controller.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Controller';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $design = $this->defineDesignMode();

        $this->makeStub('controller/controller.stub', 'controllers/{{studly_name}}.php');

        if (!$this->option('no-list')) {
            $this->makeStub('controller/config_list.stub', 'controllers/{{lower_name}}/config_list.yaml');
            $this->makeStub('controller/_list_toolbar.stub', 'controllers/{{lower_name}}/_list_toolbar.php');
            $this->makeStub('controller/index.stub', 'controllers/{{lower_name}}/index.php');
        }

        if (!$this->option('no-form')) {
            $this->makeStub('controller/config_form.stub', 'controllers/{{lower_name}}/config_form.yaml');
            if (in_array($design, ['basic', 'sidebar', 'survey'])) {
                $this->makeStub('controller/update_design.stub', 'controllers/{{lower_name}}/update.php');
                $this->makeStub('controller/preview_design.stub', 'controllers/{{lower_name}}/preview.php');
                $this->makeStub('controller/create_design.stub', 'controllers/{{lower_name}}/create.php');
            }
            elseif ($design !== 'popup') {
                $this->makeStub('controller/update.stub', 'controllers/{{lower_name}}/update.php');
                $this->makeStub('controller/preview.stub', 'controllers/{{lower_name}}/preview.php');
                $this->makeStub('controller/create.stub', 'controllers/{{lower_name}}/create.php');
            }
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
            'model' => $this->defineModelName(),
            'design' => $this->defineDesignMode(),
            'form' => !$this->option('no-form'),
            'list' => !$this->option('no-list'),
        ];
    }

    /**
     * defineModelName to use, either supplied or singular from the controller name
     */
    protected function defineModelName(): string
    {
        $model = $this->option('model');

        if (!$model) {
            $model = Str::singular($this->argument('name'));
        }

        return $model;

    }

    /**
     * defineDesignMode
     */
    protected function defineDesignMode(): string
    {
        if ($design = $this->option('design')) {
            return trim(strtolower($design));
        }

        return '';
    }
}
