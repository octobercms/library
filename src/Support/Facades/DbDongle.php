<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * DbDongle
 *
 * @method static mixed raw(string $sql, array $params = null)
 * @method static string rawValue(string $sql)
 * @method static string parse(string $sql, array $params = null)
 * @method static string parseGroupConcat(string $sql)
 * @method static string parseConcat(string $sql)
 * @method static string parseIfNull(string $sql)
 * @method static string parseBooleanExpression(string $sql)
 * @method static string cast(string $sql, string $asType = 'INTEGER')
 * @method static void convertTimestamps(string $table, string|array $columns = null)
 * @method static void disableStrictMode()
 * @method static string getDriver()
 * @method static string getTablePrefix()
 *
 * @see \October\Rain\Database\Dongle
 */
class DbDongle extends Facade
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'db.dongle';
    }
}
