<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static \Illuminate\Database\Schema\Builder create(string $table, \Closure $callback)
 * @method static \Illuminate\Database\Schema\Builder drop(string $table)
 * @method static \Illuminate\Database\Schema\Builder dropIfExists(string $table)
 * @method static \Illuminate\Database\Schema\Builder table(string $table, \Closure $callback)
 * @method static \Illuminate\Database\Schema\Builder rename(string $from, string $to)
 * @method static void defaultStringLength(int $length)
 * @method static bool hasTable(string $table)
 * @method static bool hasColumn(string $table, string $column)
 * @method static bool hasColumns(string $table, array $columns)
 * @method static \Illuminate\Database\Schema\Builder disableForeignKeyConstraints()
 * @method static \Illuminate\Database\Schema\Builder enableForeignKeyConstraints()
 * @method static void registerCustomDoctrineType(string $class, string $name, string $type)
 *
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        $builder = static::$app['db']->connection($name)->getSchemaBuilder();

        static::$app['events']->fire('db.schema.getBuilder', [$builder]);

        return $builder;
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        $builder = static::$app['db']->connection()->getSchemaBuilder();

        static::$app['events']->fire('db.schema.getBuilder', [$builder]);

        return $builder;
    }
}
