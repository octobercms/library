<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use October\Rain\Database\Query\Grammars\MySqlGrammar;
use October\Rain\Database\Query\Grammars\PostgresGrammar;
use October\Rain\Database\Query\Grammars\SQLiteGrammar;
use October\Rain\Database\Query\Grammars\SqlServerGrammar;
use October\Rain\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testSelectConcat()
    {
        // MySQL
        $query = $this->getMySqlBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select `id`, concat(`field`, \' \', `cast`) as `full_cast`, concat(`field2`, \' \', `cast2`) as `full_cast2`',
            $query->toSql()
        );

        $query = $this->getMySqlBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select `id`, concat(\'field\', \' \', `cast`) as `full_cast`',
            $query->toSql()
        );

        // SQLite
        $query = $this->getSQLiteBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", "field" || \' \' || "cast" as "full_cast", "field2" || \' \' || "cast2" as "full_cast2"',
            $query->toSql()
        );

        $query = $this->getSQLiteBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", \'field\' || \' \' || "cast" as "full_cast"',
            $query->toSql()
        );

        // PostgreSQL
        $query = $this->getPostgresBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select "id", concat("field", \' \', "cast") as "full_cast", concat("field2", \' \', "cast2") as "full_cast2"',
            $query->toSql()
        );

        $query = $this->getPostgresBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select "id", concat(\'field\', \' \', "cast") as "full_cast"',
            $query->toSql()
        );

        // SQL Server
        $query = $this->getSqlServerBuilder()
            ->select(['id'])
            ->selectConcat(['field', ' ', 'cast'], 'full_cast')
            ->selectConcat(['field2', ' ', 'cast2'], 'full_cast2');

        $this->assertEquals(
            'select [id], concat([field], \' \', [cast]) as [full_cast], concat([field2], \' \', [cast2]) as [full_cast2]',
            $query->toSql()
        );

        $query = $this->getSqlServerBuilder()
            ->select(['id'])
            ->selectConcat(['"field"', ' ', 'cast'], 'full_cast');

        $this->assertEquals(
            'select [id], concat(\'field\', \' \', [cast]) as [full_cast]',
            $query->toSql()
        );
    }

    public function testUpsert()
    {
        // MySQL
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `email` = values(`email`), `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        // PostgreSQL
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "email" = "excluded"."email", "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        // SQLite
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "email" = "excluded"."email", "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);

        // SQL Server
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [email] = [laravel_source].[email], [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name])', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email');
        $this->assertEquals(2, $result);
    }

    public function testUpsertWithUpdateColumns()
    {
        // MySQL
        $builder = $this->getMySqlBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into `users` (`email`, `name`) values (?, ?), (?, ?) on duplicate key update `name` = values(`name`)', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        // PostgreSQL
        $builder = $this->getPostgresBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        // SQLite
        $builder = $this->getSQLiteBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('insert into "users" ("email", "name") values (?, ?), (?, ?) on conflict ("email") do update set "name" = "excluded"."name"', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);

        // SQL Server
        $builder = $this->getSqlServerBuilder();
        $builder->getConnection()
            ->expects($this->once())
            ->method('affectingStatement')
            ->with('merge [users] using (values (?, ?), (?, ?)) [laravel_source] ([email], [name]) on [laravel_source].[email] = [users].[email] when matched then update set [name] = [laravel_source].[name] when not matched then insert ([email], [name]) values ([email], [name])', ['foo', 'bar', 'foo2', 'bar2'])
            ->willReturn(2);
        $result = $builder->from('users')->upsert([['email' => 'foo', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], 'email', ['name']);
        $this->assertEquals(2, $result);
    }

    protected function getConnection($connection = null)
    {
        if ($connection) {
            return parent::getConnection($connection);
        }
        
        $connection = $this->getMockBuilder(ConnectionInterface::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods([
                'table',
                'raw',
                'selectOne',
                'select',
                'cursor',
                'insert',
                'update',
                'delete',
                'statement',
                'affectingStatement',
                'unprepared',
                'prepareBindings',
                'transaction',
                'beginTransaction',
                'commit',
                'rollBack',
                'transactionLevel',
                'pretend',
            ])
            ->addMethods([
                'getDatabaseName',
            ])
            ->getMock();

        $connection->method('getDatabaseName')->willReturn('database');

        return $connection;
    }

    protected function getBuilder()
    {
        $grammar = new Grammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getMySqlBuilder()
    {
        $grammar = new MySqlGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getPostgresBuilder()
    {
        $grammar = new PostgresGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getSQLiteBuilder()
    {
        $grammar = new SQLiteGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }

    protected function getSqlServerBuilder()
    {
        $grammar = new SqlServerGrammar;
        $processor = $this->createMock(Processor::class);

        return new QueryBuilder($this->getConnection(), $grammar, $processor);
    }
}
