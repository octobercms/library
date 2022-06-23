<?php namespace October\Rain\Assetic;

use October\Rain\Assetic\Asset\AssetInterface;
use InvalidArgumentException;

/**
 * AssetManager manages assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetManager
{
    /**
     * @var array assets
     */
    protected $assets = [];

    /**
     * get an asset by name.
     *
     * @param string $name The asset name
     * @return AssetInterface The asset
     * @throws InvalidArgumentException If there is no asset by that name
     */
    public function get($name)
    {
        if (!isset($this->assets[$name])) {
            throw new InvalidArgumentException(sprintf('There is no "%s" asset.', $name));
        }

        return $this->assets[$name];
    }

    /**
     * has checks if the current asset manager has a certain asset.
     *
     * @param string $name an asset name
     * @return Boolean True if the asset has been set, false if not
     */
    public function has($name)
    {
        return isset($this->assets[$name]);
    }

    /**
     * set registers an asset to the current asset manager.
     *
     * @param string         $name  The asset name
     * @param AssetInterface $asset The asset
     * @throws InvalidArgumentException If the asset name is invalid
     */
    public function set($name, AssetInterface $asset)
    {
        if (!ctype_alnum(str_replace('_', '', $name))) {
            throw new InvalidArgumentException(sprintf('The name "%s" is invalid.', $name));
        }

        $this->assets[$name] = $asset;
    }

    /**
     * getNames returns an array of asset names.
     *
     * @return array An array of asset names
     */
    public function getNames()
    {
        return array_keys($this->assets);
    }

    /**
     * clear clears all assets.
     */
    public function clear()
    {
        $this->assets = [];
    }
}
