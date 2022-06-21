<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Validator as ValidatorBase;

/**
 * Validator
 *
 * @deprecated use \Validator
 * @see \Illuminate\Database\Schema\Builder
 */
class Validator extends ValidatorBase
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'validator';
    }
}
