<?php namespace October\Rain\Scaffold\Console;

use Exception;
use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateTheme extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:theme';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new theme.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Theme';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'theme/assets/js/app.stub' => 'assets/js/app.js',
        'theme/assets/less/theme.stub' => 'assets/less/theme.less',
        'theme/layouts/default.stub' => 'layouts/default.htm',
        'theme/pages/404.stub' => 'pages/404.htm',
        'theme/pages/error.stub' => 'pages/error.htm',
        'theme/pages/home.stub' => 'pages/home.htm',
        'theme/partials/meta/seo.stub' => 'partials/meta/seo.htm',
        'theme/partials/meta/styles.stub' => 'partials/meta/styles.htm',
        'theme/partials/site/header.stub' => 'partials/site/header.htm',
        'theme/partials/site/footer.stub' => 'partials/site/footer.htm',
        'theme/theme.stub' => 'theme.yaml',
        'theme/version.stub' => 'version.yaml',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        /*
         * Extract the author and name from the plugin code
         */
        $code = str_slug($this->argument('theme'));

        return [
            'code' => $code,
        ];
    }

    /**
     * Get the plugin path from the input.
     *
     * @return string
     */
    protected function getDestinationPath()
    {
        $code = $this->prepareVars()['code'];

        return themes_path($code);
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeStub($stubName)
    {
        if (!isset($this->stubs[$stubName])) {
            return;
        }

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $this->stubs[$stubName];
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content and path
         */
        foreach ($this->vars as $key => $var) {
            $destinationContent = str_replace('{{' . $key . '}}', $var, $destinationContent);
            $destinationFile = str_replace('{{' . $key . '}}', $var, $destinationFile);
        }

        $this->makeDirectory($destinationFile);

        /*
         * Make sure this file does not already exist
         */
        if ($this->files->exists($destinationFile) && !$this->option('force')) {
            throw new Exception('Stop everything!!! This file already exists: ' . $destinationFile);
        }

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['theme', InputArgument::REQUIRED, 'The code of the theme to create. Eg: example.com'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
