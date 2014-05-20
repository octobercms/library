<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class DbDongle extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Database\Dongle
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'db.dongle'; }
}
