<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Network Http Facade
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 *
 * @method static \October\Rain\Network\Http make(string $url, $method, $options = null)
 * @method static \October\Rain\Network\Http get(string $url, $options = null)
 * @method static \October\Rain\Network\Http post(string $url, $options = null)
 * @method static \October\Rain\Network\Http delete(string $url, $options = null)
 * @method static \October\Rain\Network\Http patch(string $url, $options = null)
 * @method static \October\Rain\Network\Http put(string $url, $options = null)
 * @method static \October\Rain\Network\Http options(string $url, $options = null)
 * @method \October\Rain\Network\Http send()
 * @method \October\Rain\Network\Http header(string $key, $value = null)
 * @method \October\Rain\Network\Http proxy(string $type, string $host, $port, $username = null, $password = null)
 * @method \October\Rain\Network\Http auth(string $user, $pass = null)
 * @method \October\Rain\Network\Http data(string $key, $value = null)
 * @method \October\Rain\Network\Http noRedirect()
 * @method \October\Rain\Network\Http verifySSL()
 * @method \October\Rain\Network\Http timeout(int $timeout)
 * @method \October\Rain\Network\Http toFile(string $path, $filter = null)
 * @method \October\Rain\Network\Http setOption(string $option, $value = null)
 *
 * @see \October\Rain\Network\Http
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'network.http';
    }
}
