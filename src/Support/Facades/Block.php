<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static void put(string $name)
 * @method static void startBlock(string $name)
 * @method static void endPut(bool $append = false)
 * @method static void endBlock(bool $append = false)
 * @method static void set(string $name, string $content)
 * @method static void append(string $name, string $content)
 * @method static string placeholder(string $name, string $default = null)
 * @method static string get(string $name, string $default = null)
 * @method static void reset()
 *
 * @see \October\Rain\Html\BlockBuilder
 */
class Block extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'block';
    }
}
