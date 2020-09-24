<?php
use October\Rain\Database\Models\Revision;

class SelectConcatTest extends TestCase
{
    public function testMySqlConcat()
    {
        $capsule = new October\Rain\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'mysql',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $model = new Revision;

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select `id`, concat(`field`, \' \', `cast`) as `full_cast`, concat(`field2`, \' \', `cast2`) as `full_cast2` from `revisions`',
            $query->toSql()
        );

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select `id`, concat(\'field\', \' \', `cast`) as `full_cast` from `revisions`',
            $query->toSql()
        );
    }

    public function testSQLiteConcat()
    {
        $capsule = new October\Rain\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $model = new Revision;

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", "field" || \' \' || "cast" as "full_cast", "field2" || \' \' || "cast2" as "full_cast2" from "revisions"',
            $query->toSql()
        );

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", \'field\' || \' \' || "cast" as "full_cast" from "revisions"',
            $query->toSql()
        );
    }

    public function testPostgresqlConcat()
    {
        $capsule = new October\Rain\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'pgsql',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $model = new Revision;

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", concat("field", \' \', "cast") as "full_cast", concat("field2", \' \', "cast2") as "full_cast2" from "revisions"',
            $query->toSql()
        );

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", concat(\'field\', \' \', "cast") as "full_cast" from "revisions"',
            $query->toSql()
        );
    }

    public function testSqlServerConcat()
    {
        $capsule = new October\Rain\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlsrv',
            'database' => ':memory:',
            'prefix'   => ''
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $model = new Revision;

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select [id], concat([field], \' \', [cast]) as [full_cast], concat([field2], \' \', [cast2]) as [full_cast2] from [revisions]',
            $query->toSql()
        );

        $query = $model
            ->newQuery()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select [id], concat(\'field\', \' \', [cast]) as [full_cast] from [revisions]',
            $query->toSql()
        );
    }
}
