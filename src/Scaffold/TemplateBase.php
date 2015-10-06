<?php namespace October\Rain\Scaffold;

use Exception;
use October\Rain\Support\Str;
use October\Rain\Filesystem\Filesystem;

/**
 * Base class for scaffolding templates.
 *
 * The template simply provides a file mapping property.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class TemplateBase
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
     * @var bool Flag to overwrite files or not.
     */
    protected $overwriteFiles = false;

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
     * @param string $path Root path to output generated files
     * @param array $vars Variables to pass to the stub templates
     * @return void
     */
    public static function make($path, $vars = [], $force = false)
    {
        $self = new static($path, $vars);

        if ($force)
            $self->setOverwrite(true);

        return $self->makeAll();
    }

    /**
     * Sets the overwrite files flag on
     */
    public function setOverwrite($value)
    {
        $this->overwriteFiles = $value;
    }

    /**
     * Make all stubs
     * @return void
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
     * @param $stubName The source filename for the stub.
     * @return void
     */
    public function makeStub($stubName)
    {
        if (!isset($this->fileMap[$stubName]))
            return;

        $sourceFile = __DIR__ . '/Templates/' . $stubName;
        $destinationFile = $this->targetPath . '/' . $this->fileMap[$stubName];
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the desintation content and path
         */
        foreach ($this->vars as $key => $var) {
            $destinationContent = str_replace('{{'.$key.'}}', $var, $destinationContent);
            $destinationFile = str_replace('{{'.$key.'}}', $var, $destinationFile);
        }

        /*
         * Destination directory must exist
         */
        $destinationDirectory = dirname($destinationFile);
        if (!$this->files->exists($destinationDirectory))
            $this->files->makeDirectory($destinationDirectory, 0777, true); // @todo 777 not supported everywhere

        /*
         * Make sure this file does not already exist
         */
        if ($this->files->exists($destinationFile) && !$this->overwriteFiles)
            throw new \Exception('Stop everything!!! This file already exists: ' . $destinationFile);

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * Converts all variables to available modifier and case formats.
     * Syntax is CASE_MODIFIER_KEY, eg: lower_plural_xxx
     *
     * @param array The collection of original variables
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

}