<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Schema extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \October\Rain\Database\Schema\Builder
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
     * @return \October\Rain\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        $builder = static::$app['db']->connection()->getSchemaBuilder();

        static::$app['events']->fire('db.schema.getBuilder', [$builder]);

        return $builder;
    }
}
