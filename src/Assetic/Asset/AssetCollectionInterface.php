<?php namespace October\Rain\Assetic\Asset;

/**
 * AssetCollectionInterface is an asset collection.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface AssetCollectionInterface extends AssetInterface, \Traversable
{
    /**
     * Returns all child assets.
     *
     * @return array An array of AssetInterface objects
     */
    public function all();

    /**
     * Adds an asset to the current collection.
     *
     * @param AssetInterface $asset An asset
     */
    public function add(AssetInterface $asset);

    /**
     * Removes a leaf.
     *
     * @param AssetInterface $leaf The leaf to remove
     * @param Boolean $graceful Whether the failure should return false or throw an exception
     *
     * @return Boolean Whether the asset has been found
     *
     * @throws \InvalidArgumentException If the asset cannot be found
     */
    public function removeLeaf(AssetInterface $leaf, $graceful = false);

    /**
     * Replaces an existing leaf with a new one.
     *
     * @param AssetInterface $needle The current asset to replace
     * @param AssetInterface $replacement The new asset
     * @param Boolean $graceful Whether the failure should return false or throw an exception
     *
     * @return Boolean Whether the asset has been found
     *
     * @throws \InvalidArgumentException If the asset cannot be found
     */
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, $graceful = false);
}
