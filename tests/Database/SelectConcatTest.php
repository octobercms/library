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
            ->selectConcat(['field', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select `id`, CONCAT(`field`, " ", `cast`) AS `full_cast` from `revisions`',
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
            ->selectConcat(['field', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", "field" || " " || "cast" AS "full_cast" from "revisions"',
            $query->toSql()
        );
    }
}
