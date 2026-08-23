<?php namespace October\Rain\Scaffold\Console;

use October\Rain\Support\Str;
use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateTheme
 */
class CreateTheme extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:theme
        {name : The name of the theme to create. <info>(eg: MyTheme)</info>}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new theme.';

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel = 'Theme';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('theme/theme.stub', 'theme.yaml');
        $this->makeStub('theme/version.stub', 'version.yaml');
        $this->makeStub('theme/composer.stub', 'composer.json');
        $this->makeStub('theme/layout.stub', 'layouts/default.htm');
        $this->makeStub('theme/page.stub', 'pages/index.htm');
        $this->makeStub('theme/gitkeep.stub', 'partials/.gitkeep');
        $this->makeStub('theme/gitkeep.stub', 'content/.gitkeep');
        $this->makeStub('theme/gitkeep.stub', 'assets/.gitkeep');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        return [
            'name' => $this->argument('name'),
            'slug' => Str::slug($this->argument('name')),
        ];
    }

    /**
     * getDestinationPath returns the theme directory
     */
    protected function getDestinationPath(): string
    {
        return themes_path(Str::slug($this->argument('name')));
    }

    /**
     * getNamespaceInput uses the theme name since themes have no author namespace
     */
    protected function getNamespaceInput(): string
    {
        return $this->argument('name');
    }
}