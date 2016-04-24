<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;
use October\Rain\Database\Schema\Blueprint;

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
        return static::getBuilder($name);
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \October\Rain\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return static::getBuilder();
    }

    protected static function getBuilder($connection = null)
    {
        $builder = static::$app['db']->connection($connection)->getSchemaBuilder();

        $builder->blueprintResolver(function($table, $callback) {
            return new Blueprint($table, $callback);
        });

        return $builder;
    }
}
