<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Validator as ValidatorBase;

/**
 * Validator
 *
 * @see \October\Rain\Validation\Factory
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
