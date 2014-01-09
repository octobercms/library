<?php namespace October\Rain\Boilerplate;

use Exception;
use October\Rain\Support\Str;
use October\Rain\Filesystem\Filesystem;

abstract class Base
{
    /**
     * @var string The target path where generated files should be created.
     */
    protected $targetPath;

    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [];

    /**
     * @var array An array of variables to use.
     */
    protected $vars = [];

    /**
     * @var Filesystem File helper object
     */
    protected $files;

    /**
     * Constructor
     */
    public function __construct($path = null, $vars = [])
    {
        $this->files = new Filesystem;

        if ($path !== null && !$this->files->isWritable($path))
            throw new Exception(sprintf('Path "%s" is not writable', $path));

        $this->targetPath = $path;
        $this->vars = $this->processVars($vars);
    }

    /**
     * Static helper
     */
    public static function make($path, $vars = [])
    {
        $self = new static($path, $vars);
        return $self->makeAll();
    }

    /**
     * Make all stubs
     */
    public function makeAll()
    {
        $stubs = array_keys($this->fileMap);
        foreach ($stubs as $stub) {
            $this->makeStub($stub);
        }
    }

    /**
     * Make a single stub
     */
    public function makeStub($stubName)
    {
        if (!isset($this->fileMap[$stubName]))
            return;

        $sourceFile = __DIR__ . '/' . $stubName;
        $destinationFile = $this->targetPath . '/' . $this->fileMap[$stubName];
        $destinationDirectory = dirname($destinationFile);

        if (!$this->files->exists($destinationDirectory))
            $this->files->makeDirectory($destinationDirectory, 0777, true); // @todo 777 not supported everywhere

        $destinationContent = $this->files->get($sourceFile);

        foreach ($this->vars as $key => $var) {
            $destinationContent  = str_replace('{{'.$key.'}}', $var, $destinationContent);
        }

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * Converts all variables to available modifier and case formats.
     * Syntax is CASE_MODIFIER_KEY, eg: lower_plural_xxx
     */
    private function processVars($vars)
    {
        $cases = ['upper', 'lower', 'snake', 'studly', 'camel'];
        $modifiers = ['plural', 'singular'];

        foreach ($vars as $key => $var) {

            /*
             * Apply cases, and cases with modifiers
             */
            foreach ($cases as $case) {
                $primaryKey = $case . '_' . $key;
                $vars[$primaryKey] = Str::$case($var);

                foreach ($modifiers as $modifier) {
                    $secondaryKey = $case . '_' . $modifier . '_' . $key;
                    $vars[$secondaryKey] = Str::$case(Str::$modifier($var));
                }
            }

            /*
             * Apply modifiers
             */
            foreach ($modifiers as $modifier) {
                $primaryKey = $modifier . '_' . $key;
                $vars[$primaryKey] = Str::$modifier($var);
            }

        }

        return $vars;
    }

}