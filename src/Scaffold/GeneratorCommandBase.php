<?php namespace October\Rain\Scaffold;

use Twig;
use October\Rain\Support\Str;
use Illuminate\Console\Command;
use October\Rain\Filesystem\Filesystem;
use ReflectionClass;
use Exception;

/**
 * GeneratorCommandBase base class
 */
abstract class GeneratorCommandBase extends Command
{
    /**
     * @var \October\Rain\Filesystem\Filesystem files is the filesystem instance
     */
    protected $files;

    /**
     * @var string typeLabel of class being generated
     */
    protected $typeLabel;

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

        $this->components->info("{$this->typeLabel} created successfully.");
    }

    /**
     * prepareVars prepares variables for stubs
     */
    abstract protected function prepareVars();

    /**
     * makeStubs makes all stubs
     */
    abstract public function makeStubs();

    /**
     * makeStub makes a single stub
     */
    public function makeStub(string $stubName, string $outputName)
    {
        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationPath() . '/' . $outputName;
        $destinationContent = $this->files->get($sourceFile);

        // Parse each variable in to the destination content and path
        $destinationContent = Twig::parse($destinationContent, $this->vars);
        $destinationFile = Twig::parse($destinationFile, $this->vars);

        $this->makeDirectory($destinationFile);

        // Make sure this file does not already exist
        if ($this->files->exists($destinationFile) && !$this->option('overwrite')) {
            throw new Exception('Process halted! This file already exists: ' . $destinationFile);
        }

        $this->files->put($destinationFile, $destinationContent);
    }

    /**
     * makeDirectory builds the directory for the class if necessary
     */
    protected function makeDirectory(string $path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true, true);
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
            // Process namespace manually
            if ($key === 'namespace') {
                continue;
            }

            // Apply cases, and cases with modifiers
            foreach ($cases as $case) {
                $primaryKey = $case . '_' . $key;
                $vars[$primaryKey] = $this->modifyString($case, $var);

                foreach ($modifiers as $modifier) {
                    $secondaryKey = $case . '_' . $modifier . '_' . $key;
                    $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                }
            }

            // Apply modifiers
            foreach ($modifiers as $modifier) {
                $primaryKey = $modifier . '_' . $key;
                $vars[$primaryKey] = $this->modifyString($modifier, $var);
            }
        }

        // Namespace specific
        if (isset($vars['namespace'])) {
            $vars['namespace_php'] = $this->getNamespacePhp();
            $vars['namespace_table'] = $this->getNamespaceTable();
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
     * getNamespaceTable
     */
    protected function getNamespaceTable(): string
    {
        if ($this->isAppNamespace()) {
            return 'app';
        }

        [$author, $name] = $this->getFormattedNamespace();
        $author = mb_strtolower($author);
        $name = mb_strtolower($name);

        return "{$author}_{$name}";
    }

    /**
     * getNamespacePhp
     */
    protected function getNamespacePhp(): string
    {
        if ($this->isAppNamespace()) {
            return 'App';
        }

        [$author, $name] = $this->getFormattedNamespace();
        $author = Str::studly($author);
        $name = Str::studly($name);

        return "{$author}\\{$name}";
    }

    /**
     * getDestinationPath gets the plugin path from the input
     */
    protected function getDestinationPath(): string
    {
        if ($this->isAppNamespace()) {
            return app_path();
        }

        [$author, $name] = $this->getFormattedNamespace();
        $author = mb_strtolower($author);
        $name = mb_strtolower($name);

        return plugins_path("{$author}/{$name}");
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
     * getFormattedNamespace returns a tuple of author and plugin name, or app,
     * where returned array takes format of [author, name]
     */
    protected function getFormattedNamespace(): array
    {
        $namespace = $this->getNamespaceInput();

        if (strpos($namespace, '.') !== false) {
            $parts = explode('.', $namespace);
            return [$parts[0], $parts[1]];
        }

        if (strpos($namespace, '\\') !== false) {
            $parts = explode('\\', $namespace);
            return [$parts[0], $parts[1]];
        }

        return [$namespace, null];
    }

    /**
     * getNamespaceInput gets the desired plugin name from the input
     */
    protected function getNamespaceInput(): string
    {
        return $this->argument('namespace');
    }

    /**
     * isAppNamespace
     */
    protected function isAppNamespace(): bool
    {
        return mb_strtolower(trim($this->getNamespaceInput())) === 'app';
    }
}
