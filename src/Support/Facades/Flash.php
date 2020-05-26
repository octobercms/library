<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static bool check()
 * @method static array all(string $format = null)
 * @method static array get(string $key, string $format = null)
 * @method static array|\October\Rain\Flash\FlashBag error(string $message = null)
 * @method static array|\October\Rain\Flash\FlashBag success(string $message = null)
 * @method static array|\October\Rain\Flash\FlashBag warning(string $message = null)
 * @method static array|\October\Rain\Flash\FlashBag info(string $message = null)
 * @method static \October\Rain\Flash\FlashBag add(string $key, string $message)
 * @method static void store()
 * @method static void forget(string $key = null)
 * @method static void purge();
 *
 * @see \October\Rain\Flash\FlashBag
 */
class Flash extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'flash';
    }
}
