<?php

use October\Rain\Database\Schema\Blueprint;

/**
 * BlueprintTest
 */
class BlueprintTest extends TestCase
{
    /**
     * @var Illuminate\Database\Capsule\Manager capsule shared across tests so a single
     * :memory: connection is reused, keeping table drops and creates reliable
     */
    protected static $capsule;

    /**
     * @var Illuminate\Database\Schema\Builder schema builder using the October blueprint
     */
    protected $schema;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        if (!self::$capsule) {
            self::$capsule = new Illuminate\Database\Capsule\Manager;
            self::$capsule->addConnection([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => ''
            ]);
        }

        $this->schema = self::$capsule->getConnection()->getSchemaBuilder();
        $this->schema->blueprintResolver(function ($connection, $table, $callback) {
            return new Blueprint($connection, $table, $callback);
        });

        $this->schema->dropIfExists('test_multisite_records');
    }

    /**
     * testMultisiteIndexesRootColumn confirms site_root_id gets its own index alongside the composite index
     */
    public function testMultisiteIndexesRootColumn()
    {
        $this->schema->create('test_multisite_records', function (Blueprint $table) {
            $table->increments('id');
            $table->multisite();
        });

        $indexes = $this->getIndexColumns('test_multisite_records');

        $this->assertSame(['site_id', 'site_root_id'], $indexes['test_multisite_records_site_id_site_root_id_index'] ?? null);
        $this->assertSame(['site_root_id'], $indexes['test_multisite_records_site_root_id_index'] ?? null);
    }

    /**
     * testMultisiteNamesRootIndexFromCustomName confirms a custom index name also names the root index
     */
    public function testMultisiteNamesRootIndexFromCustomName()
    {
        $this->schema->create('test_multisite_records', function (Blueprint $table) {
            $table->increments('id');
            $table->multisite('site_id', 'multisite_idx');
        });

        $indexes = $this->getIndexColumns('test_multisite_records');

        $this->assertSame(['site_id', 'site_root_id'], $indexes['multisite_idx'] ?? null);
        $this->assertSame(['site_root_id'], $indexes['multisite_idx_root'] ?? null);
    }

    /**
     * getIndexColumns returns the indexed columns of a table keyed by index name
     */
    protected function getIndexColumns(string $table): array
    {
        return array_column($this->schema->getIndexes($table), 'columns', 'name');
    }
}
