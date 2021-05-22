<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * DbDongle
 *
 * @see \October\Rain\Database\Dongle
 */
class DbDongle extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'db.dongle';
    }
}
