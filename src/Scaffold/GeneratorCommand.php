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
     * @var \October\Rain\Filesystem\Filesystem files is the filesystem instance
     */
    protected $files;

    /**
     * @var string type of class being generated
     */
    protected $type;

    /**
     * @var array stubs is a mapping of stub to generated file
     */
    protected $stubs = [];

    /**
     * @var array vars to use in stubs
     */
    protected $vars = [];

    /**
     * __construct creates a new controller creator command instance
     */
    public function __construct()
    {
        parent::__construct();

        $this->files = new Filesystem;
    }

    /**
     * handle executes the console command
     */
    public function handle()
    {
        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        $this->info($this->type . ' created successfully.');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    abstract protected function prepareVars();

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $stubs = array_keys($this->stubs);

        foreach ($stubs as $stub) {
            $this->makeStub($stub);
        }
    }

    /**
     * makeStub makes a single stub
     */
    public function makeStub(string $stubName)
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
     * makeDirectory builds the directory for the class if necessary
     */
    protected function makeDirectory(string $path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * processVars converts all variables to available modifier and case formats
     * Syntax is CASE_MODIFIER_KEY, eg: lower_plural_xxx
     */
    protected function processVars(array $vars): array
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
     * modifyString is an internal helper that handles modify a string, with extra logic
     */
    protected function modifyString($type, string $string): string
    {
        if (is_array($type)) {
            foreach ($type as $_type) {
                $string = $this->modifyString($_type, $string);
            }

            return $string;
        }

        if ($type === 'title') {
            $string = str_replace('_', ' ', Str::snake($string));
        }

        return Str::$type($string);
    }

    /**
     * getDestinationPath gets the plugin path from the input
     */
    protected function getDestinationPath(): string
    {
        $plugin = $this->getPluginInput();

        $parts = explode('.', $plugin);
        $name = array_pop($parts);
        $author = array_pop($parts);

        return plugins_path(strtolower($author) . '/' . strtolower($name));
    }

    /**
     * getSourcePath gets the source file path
     */
    protected function getSourcePath(): string
    {
        $className = get_class($this);
        $class = new ReflectionClass($className);

        return dirname($class->getFileName());
    }

    /**
     * getPluginInput gets the desired plugin name from the input
     */
    protected function getPluginInput(): string
    {
        return $this->argument('plugin');
    }

    /**
     * getArguments get the console command arguments
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
        ];
    }

    /**
     * getOptions get the console command options
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
        ];
    }
}
