<?php namespace October\Rain\Halcyon;

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Theme\ThemeInterface;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\MissingFileNameException;

class Builder
{
    /**
     * The theme instance.
     *
     * @var \October\Rain\Halcyon\Theme\ThemeInterface
     */
    protected $theme;

    /**
     * The model being queried.
     *
     * @var \October\Rain\Halcyon\Model
     */
    protected $model;

    /**
     * The datasource query post processor instance.
     *
     * @var \October\Rain\Halcyon\Processors\Processor
     */
    protected $processor;

    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The number of minutes to cache the query.
     *
     * @var int
     */
    protected $cacheMinutes;

    /**
     * The tags for the query cache.
     *
     * @var array
     */
    protected $cacheTags;

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * Create a new query builder instance.
     *
     * @param  \October\Rain\Halcyon\Theme\ThemeInterface  $theme
     * @param  \October\Rain\Halcyon\Model  $model
     * @param  \October\Rain\Halcyon\Processors\Processor  $processor
     * @return void
     */
    public function __construct(ThemeInterface $theme, Model $model, Processor $processor)
    {
        $this->theme = $theme;
        $this->model = $model;
        $this->processor = $processor;
    }

    /**
     * Find a single template by its file name.
     *
     * @param  string $fileName
     * @return mixed|static
     */
    public function find($fileName)
    {
        $useCache = $this->cacheMinutes !== null;

        $result = $useCache
            ? $this->findCached($fileName)
            : $this->findFresh($fileName);

        if ($useCache && $this->isCacheBusted($fileName, array_get($result, 'mtime'))) {
            $result = $this->findCached($fileName, true);
        }

        if ($result === null) {
            return null;
        }

        $results = $this->getModels([$result]);

        return count($results) > 0 ? reset($results) : null;
    }

    /**
     * Find a single template by its file name, as a fresh statement.
     *
     * @param  string $fileName
     * @return mixed|static
     */
    public function findFresh($fileName)
    {
        list($name, $extension) = $this->model->getFileNameParts($fileName);
        $fileName = $name . '.' . $extension; // Normalize file name

        $result = $this->theme->selectOne(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );

        return $this->processor->processSelectOne($this, $result, $fileName);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return \October\Rain\Halcyon\Collection|static[]
     */
    public function get()
    {
        $results = $this->theme->select(
            $this->model->getObjectTypeDirName(),
            $this->model->getAllowedExtensions()
        );

        $results = $this->processor->processSelect($this, $results);

        $models = $this->getModels($results);

        return $this->model->newCollection($models);
    }

    /**
     * Insert a new record into the datasource.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }

        if (!$fileName = $this->model->fileName) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        $result = $this->processor->processInsert($this, $values);

        return $this->theme->insert(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result
        );
    }

    /**
     * Update a record in the datasource.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        if (!$fileName = $this->model->fileName) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        $result = $this->processor->processUpdate($this, $values);

        $oldName = $oldExtension = null;

        if ($this->model->isDirty('fileName')) {
            list($oldName, $oldExtension) = $this->model->getFileNameParts(
                $this->model->getOriginal('fileName')
            );
        }

        return $this->theme->update(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result,
            $oldName,
            $oldExtension
        );
    }

    /**
     * Delete a record from the database.
     *
     * @param  string  $fileName
     * @return int
     */
    public function delete($fileName = null)
    {
        if ($fileName === null && (!$fileName = $this->model->fileName)) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        return $this->theme->delete(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * Get the hydrated models.
     *
     * @param  array  $results
     * @return \October\Rain\Halcyon\Model[]
     */
    public function getModels(array $results)
    {
        $theme = $this->model->getThemeName();

        return $this->model->hydrate($results, $theme)->all();
    }

    /**
     * Get the model instance being queried.
     *
     * @return \October\Rain\Halcyon\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    //
    // Caching
    //

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        list($this->cacheMinutes, $this->cacheKey) = array($minutes, $key);

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
     * @return $this
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;
        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
        return $this;
    }

    /**
     * Execute the query as a cached "select" statement.
     *
     * @param  string  $fileName
     * @param  string  $forget
     * @return array
     */
    public function findCached($fileName, $forget = false)
    {
        $key = $this->getCacheKey($fileName);
        $minutes = $this->cacheMinutes;
        $cache = $this->getCache();

        $callback = $this->getCacheCallback($fileName);

        if ($forget) {
            $cache->forget($key);
        }

        // If the "minutes" value is less than zero, we will use that as the indicator
        // that the value should be remembered values should be stored indefinitely
        // and if we have minutes we will use the typical remember function here.
        if ($minutes < 0) {
            return $cache->rememberForever($key, $callback);
        }

        return $cache->remember($key, $minutes, $callback);
    }

    /**
     * Returns true if the cache for the file is busted.
     */
    protected function isCacheBusted($fileName, $mtime)
    {
        list($name, $extension) = $this->model->getFileNameParts($fileName);

        $currentMtime = $this->theme->lastModified(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );

        return $currentMtime != $mtime;
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = $this->model->getCacheManager()->driver($this->cacheDriver);

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Get a unique cache key for the complete query.
     *
     * @return string
     */
    public function getCacheKey($fileName)
    {
        return $this->cacheKey ?: $this->generateCacheKey($fileName);
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @return string
     */
    public function generateCacheKey($fileName)
    {
        $name = $this->model->getObjectTypeDirName();

        return $name . $this->theme->makeCacheKey($fileName);
    }

    /**
     * Get the Closure callback used when caching queries.
     *
     * @param  string  $fileName
     * @return \Closure
     */
    protected function getCacheCallback($fileName)
    {
        return function() use ($fileName) { return $this->findFresh($fileName); };
    }
}
