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
    protected $signature = 'create:migration
        {namespace : App or Plugin Namespace. <info>(eg: Acme.Blog)</info>}
        {name : The name of the model. Eg: Post}
        {--create= : The table to be created}
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
     * @var bool isCreate determines if this is a creation migration
     */
    protected $isCreate = false;

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        if ($this->isAppNamespace()) {
            if ($this->isCreate) {
                $this->makeStub('migration/create_app_table.stub', 'database/migrations/'.$this->getDatePrefix().'_{{snake_name}}.php');
            }
            else {
                $this->makeStub('migration/update_app_table.stub', 'database/migrations/'.$this->getDatePrefix().'_{{snake_name}}.php');
            }
        }
        else {
            if ($this->isCreate) {
                $this->makeStub('migration/create_table.stub', 'updates/{{snake_name}}.php');
            }
            else {
                $this->makeStub('migration/update_table.stub', 'updates/{{snake_name}}.php');
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
            'table' => $this->defineTableName(),
            'softDeletes' => $this->option('soft-deletes'),
            'timestamps' => !$this->option('no-timestamps')
        ];
    }

    /**
     * defineTableName
     */
    protected function defineTableName(): string
    {
        if ($table = $this->option('table')) {
            return $table;
        }

        if ($table = $this->option('create')) {
            $this->isCreate = true;
            return $table;
        }

        return $this->guessTableName();
    }

    /**
     * guessTableName
     */
    protected function guessTableName(): string
    {
        $tableName = Str::snake($this->argument('name'));

        $createPatterns = [
            '/^create_(\w+)_table$/',
            '/^create_(\w+)$/',
        ];

        foreach ($createPatterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $tableName = $matches[1];
                $this->isCreate = true;
            }
        }

        $updatePatterns = [
            '/_(to|from|in)_(\w+)_table$/',
            '/_(to|from|in)_(\w+)$/',
        ];

        foreach ($updatePatterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $tableName = $matches[1];
            }
        }

        return $this->getNamespaceTable() . '_' .$tableName;
    }

    /**
     * getDatePrefix
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }
}
