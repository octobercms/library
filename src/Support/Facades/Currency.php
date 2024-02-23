<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Currency facade
 *
 * @method static mixed listConverters(bool $asObject)
 * @method static mixed listConverterObjects()
 * @method static mixed findConverterByAlias()
 *
 * @see \Responsiv\Shop\Classes\CurrencyManager
 */
class Currency extends Facade
{
    /**
     * getFacadeAccessor gets the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'currencies';
    }
}
