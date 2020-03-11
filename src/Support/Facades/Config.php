<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static bool has(string $key)
 * @method static bool hasGroup(string $key)
 * @method static mixed get(array|string $key, $default = null)
 * @method static array all()
 * @method static void set(array|string $key, $value)
 * @method static void prepend(string $key, $value)
 * @method static void push(string $key, $value)
 *
 * @see \October\Rain\Config\Repository
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
