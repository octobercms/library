<?php namespace October\Rain\Scaffold\Console;

use Config;
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
        'theme/theme.stub'  => 'theme.yaml',
        'theme/version.stub' => 'version.yaml',
        'theme/gitkeep.stub' => '.gitkeep',
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        /*
         * Get Console Arguments
         */
        $themeName = $this->option('name');
        $authorName = $this->option('author');
        $themeDescription = $this->option('description');
        $homePage = $this->option('homepage');

        return [
            'name'   => $themeName,
            'author' => $authorName,
            'description' => $themeDescription,
            'homepage' => $homePage
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
            ['name', null, InputOption::VALUE_REQUIRED, 'The name of the theme to create. Eg: Demo']
            ['author', null, InputOption::VALUE_OPTIONAL, 'The author of the theme.'],
            ['description', null, InputOption::VALUE_OPTIONAL, 'A description of the theme.'],
            ['homepage', null, InputOption::VALUE_OPTIONAL, 'Author\'s home page url']
        ];
    }

    /**
     * Converts all variables to available modifier and case formats.
     * Syntax is CASE_MODIFIER_KEY, eg: lower_plural_xxx
     *
     * @param array $vars The collection of original variables
     * @return array A collection of variables with modifiers added
     */
    protected function processVars($vars)
    {

        $cases = ['upper', 'lower', 'snake', 'studly', 'camel', 'title'];
        $modifiers = ['plural', 'singular', 'title'];

        foreach ($vars as $key => $var) {
            if(!empty($var)) {
                /*
                * Apply cases, and cases with modifiers
                */
                foreach ($cases as $case) {
                    $primaryKey = $case . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($case, $var);

                    foreach ($modifiers as $modifier) {
                        $secondaryKey = $case . '_' . $modifier . '_' . $key;
                        $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                    }
                }

                /*
                * Apply modifiers
                */
                foreach ($modifiers as $modifier) {
                    $primaryKey = $modifier . '_' . $key;
                    $vars[$primaryKey] = $this->modifyString($modifier, $var);
                }
            }
        }

        return $vars;
    }

    /**
     * Get the plugin path from the input.
     *
     * @return string
     */
    protected function getDestinationPath()
    {
        $name = $this->argument('name');

        return base_path(Config::get('cms.themesPath', '/themes') . '/' . strtolower($name));
    }
}
