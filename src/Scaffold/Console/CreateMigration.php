<?php namespace October\Rain\Scaffold\Console;

use Str;
use October\Rain\Scaffold\GeneratorCommandBase;

/**
 * CreateMigration
 */
class CreateMigration extends GeneratorCommandBase
{
    /**
     * @var string signature for the command
     */
    protected $signature = 'create:migration {namespace : App or Plugin Namespace (eg: RainLab.Blog)}
        {name : The name of the model. Eg: Post}
        {--table= : The table to migrate}
        {--soft-deletes : Implement soft deletion on this model}
        {--no-timestamps : Disable auto-timestamps on this model}
        {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Creates a new migration.';

    /**
     * @var string type of class being generated
     */
    protected $typeLabel = 'Migration';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('migration/create_table.stub', 'updates/{{snake_name}}.php');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        return [
            'name' => $this->argument('name'),
            'namespace' => $this->argument('namespace'),
            'table' => $this->option('table') ?: $this->guessTableName(),
            'softDeletes' => $this->option('soft-deletes'),
            'timestamps' => !$this->option('no-timestamps')
        ];
    }

    /**
     * guessTableName
     */
    protected function guessTableName(): string
    {
        $tableName = Str::snake($this->argument('name'));

        $patterns = [
            '/^create_(\w+)_table$/',
            '/^create_(\w+)$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $tableName = $matches[1];
            }
        }

        return $this->getNamespaceTable() . '_' .$tableName;
    }
}
