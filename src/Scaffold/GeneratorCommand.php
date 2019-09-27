<?php namespace October\Rain\Scaffold;

use ReflectionClass;
use October\Rain\Support\Str;
use Illuminate\Console\Command;
use October\Rain\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Twig;

abstract class GeneratorCommand extends Command
{
    /**
     * The filesystem instance.
     *
     * @var \October\Rain\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type;

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [];

    /**
     * An array of variables to use in stubs.
     *
     * @var array
     */
    protected $vars = [];

    /**
     * Create a new controller creator command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->files = new Filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        $this->info($this->type . ' created successfully.');
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    abstract protected function prepareVars();

    /**
     * Make all stubs.
     *
     * @return void
     */
    public function makeStubs()
    {
        $stubs = array_keys($this->stubs);

        foreach ($stubs as $stub) {
            $this->makeStub($stub);
        }
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
        $destinationContent = Twig::parse($destinationContent, $this->vars);
        $destinationFile = Twig::parse($destinationFile, $this->vars);

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
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
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

        return $vars;
    }

    /**
     * Internal helper that handles modify a string, with extra logic.
     *
     * @param string|array $type
     * @param string $string
     * @return string
     */
    protected function modifyString($type, $string)
    {
        if (is_array($type)) {
            foreach ($type as $_type) {
                $string = $this->modifyString($_type, $string);
            }

            return $string;
        }

        if ($type == 'title') {
            $string = str_replace('_', ' ', Str::snake($string));
        }

        return Str::$type($string);
    }

    /**
     * Get the plugin path from the input.
     *
     * @return string
     */
    protected function getDestinationPath()
    {
        $plugin = $this->getPluginInput();

        $parts = explode('.', $plugin);
        $name = array_pop($parts);
        $author = array_pop($parts);

        return plugins_path(strtolower($author) . '/' . strtolower($name));
    }

    /**
     * Get the source file path.
     *
     * @return string
     */
    protected function getSourcePath()
    {
        $className = get_class($this);
        $class = new ReflectionClass($className);

        return dirname($class->getFileName());
    }

    /**
     * Get the desired plugin name from the input.
     *
     * @return string
     */
    protected function getPluginInput()
    {
        return $this->argument('plugin');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
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
