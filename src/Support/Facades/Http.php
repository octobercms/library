<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static \October\Rain\Network\Http make(string $url, string $method, callable $options = null)
 * @method static \October\Rain\Network\Http get(string $url, array $options = null)
 * @method static \October\Rain\Network\Http post(string $url, array $options = null)
 * @method static \October\Rain\Network\Http delete(string $url, array $options = null)
 * @method static \October\Rain\Network\Http patch(string $url, array $options = null)
 * @method static \October\Rain\Network\Http put(string $url, array $options = null)
 * @method static \October\Rain\Network\Http options(string $url, array $options = null)
 * @method static \October\Rain\Network\Http send()
 * @method static string getRequestData()
 * @method static \October\Rain\Network\Http data(string $key, string $value = null)
 * @method static \October\Rain\Network\Http header(string $key, string $value = null)
 * @method static \October\Rain\Network\Http proxy(string $type, string $host, int $port, string $username = null, string $password = null)
 * @method static \October\Rain\Network\Http auth(string $user, string $pass = null)
 * @method static \October\Rain\Network\Http noRedirect()
 * @method static \October\Rain\Network\Http verifySSL()
 * @method static \October\Rain\Network\Http timeout(int $timeout)
 * @method static \October\Rain\Network\Http toFile(string $path, string $filter = null)
 * @method static \October\Rain\Network\Http setOption(string $option, string $value = null)
 *
 * @see \October\Rain\Network\Http
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'network.http';
    }
}
