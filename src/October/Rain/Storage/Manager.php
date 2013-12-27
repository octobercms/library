<?php namespace October\Rain\Storage;

/**
 * Storage manager
 *
 * This class is designed to register storage paths and creating their 
 * preconfigured driver objects for accessing these data stores.
 */

class Manager
{
    use \October\Rain\Support\Singleton;

    protected $defaultOptions;

    protected $objectCache = [];

    /**
     * Register a storage container with the storage manager.
     * @param $storeId A unique identifier for the storage container, eg: user.photos
     *
     * Manager::registerStore('user.photos', ['driver'=>'October\Rain\Storage\Drivers\S3', 'key'=>'publicKey', 'secret'=>'myPhrase!', 'bucket'=>'my.bucket']);
     * Manager::registerStore('user.photos', ['driver'=>'October\Rain\Storage\Drivers\Filesystem', 'path'=>'/home/root/data']);
     */
    public function registerStore($storeId, $options = [])
    {
        $options = $this->validateOptions($options);
        return $this->objectCache[$storeId] = $obj = new $driver($options);
    }

    /**
     * Returns a preconfigured storage driver for the supplied storage id.
     */
    public function getStore($storeId)
    {
        return $this->objectCache[$storeId];
    }

    /**
     * Sets the default options.
     */
    public function setDefaultOptions($options = [])
    {
        $this->defaultOptions = $options;
    }

    /**
     * Creates an empty file object used for adding to a store.
     */
    public function createFile($storeId)
    {
        if (!isset($this->objectCache[$storeId]))
            throw new \Exception('There is no storage container registered for '. $storeId);

        return new File($this->objectCache[$storeId]);
    }

    protected function validateOptions($options = null)
    {
        if ($options === null && $this->defaultOptions === null)
            throw new \Exception('There are no default options set in Storage Manager.');

        $options = array_merge($this->defaultOptions, $options);

        if (!isset($options['driver']))
            throw new \Exception('You must define a storage driver.');

        return $options;
    }

}