<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Schema as SchemaBase;

/**
 * Schema
 *
 * @see \Illuminate\Database\Schema\Builder
 */
class Schema extends SchemaBase
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'db.schema';
    }
}
