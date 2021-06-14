<?php namespace October\Rain\Halcyon;

use October\Rain\Halcyon\Exception\InvalidDirectoryNameException;
use October\Rain\Halcyon\Exception\InvalidExtensionException;
use October\Rain\Halcyon\Exception\MissingFileNameException;
use October\Rain\Halcyon\Exception\InvalidFileNameException;
use October\Rain\Halcyon\Datasource\DatasourceInterface;
use October\Rain\Halcyon\Processors\Processor;
use BadMethodCallException;
use ApplicationException;

/**
 * Builder for Halcyon queries
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class Builder
{
    /**
     * @var \October\Rain\Halcyon\Datasource\DatasourceInterface datasource instance
     */
    protected $datasource;

    /**
     * @var \October\Rain\Halcyon\Model model being queried
     */
    protected $model;

    /**
     * @var \October\Rain\Halcyon\Processors\Processor processor datasource query post processor instance
     */
    protected $processor;

    /**
     * @var array columns that should be returned
     */
    public $columns;

    /**
     * @var array extensions filter the query by these file extensions
     */
    public $extensions;

    /**
     * @var string from the directory name which the query is targeting
     */
    public $from;

    /**
     * @var bool selectSingle query should pluck a single record
     */
    public $selectSingle;

    /**
     * @var string fileMatch using the specified pattern
     */
    public $fileMatch;

    /**
     * @var array orders is the orderings for the query
     */
    public $orders;

    /**
     * @var int limit is the maximum number of records to return
     */
    public $limit;

    /**
     * @var int offset is the number of records to skip
     */
    public $offset;

    /**
     * @var string cacheKey that should be used when caching the query
     */
    protected $cacheKey;

    /**
     * @var int cacheMinutes number of minutes to cache the query
     */
    protected $cacheMinutes;

    /**
     * @var array cacheTags for the query cache
     */
    protected $cacheTags;

    /**
     * @var string cacheDriver  to be used
     */
    protected $cacheDriver;

    /**
     * @var bool loadedFromCache is an internal variable to specify if the record was loaded from cache
     */
    protected $loadedFromCache = false;

    /**
     * __construct creates a new query builder instance
     */
    public function __construct(DatasourceInterface $datasource, Processor $processor)
    {
        $this->datasource = $datasource;
        $this->processor = $processor;
    }

    /**
     * whereFileName switches mode to select a single template by its name
     * @param  string  $fileName
     * @return $this
     */
    public function whereFileName($fileName)
    {
        $this->selectSingle = $this->model->getFileNameParts($fileName);

        return $this;
    }

    /**
     * from sets the directory name which the query is targeting
     * @param  string  $dirName
     * @return $this
     */
    public function from($dirName)
    {
        $this->from = $dirName;

        return $this;
    }

    /**
     * offset sets the "offset" value of the query
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * skip is an alias to set the "offset" value of the query
     * @param  int  $value
     * @return \October\Rain\Halcyon\Builder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * limit sets the "limit" value of the query
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * take is an alias to set the "limit" value of the query
     * @param  int  $value
     * @return \October\Rain\Halcyon\Builder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * find a single template by its file name.
     * @param  string $fileName
     * @return mixed|static
     */
    public function find($fileName)
    {
        return $this->whereFileName($fileName)->first();
    }

    /**
     * first executes the query and get the first result
     * @return mixed|static
     */
    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * get executes the query as a "select" statement
     * @param  array  $columns
     * @return \October\Rain\Halcyon\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        if (!is_null($this->cacheMinutes)) {
            $results = $this->getCached($columns);
        }
        else {
            $results = $this->getFresh($columns);
        }

        $models = $this->getModels($results ?: []);

        return $this->model->newCollection($models);
    }

    /**
     * lists gets an array with the values of a given column
     * @param  string  $column
     * @param  string  $key
     * @return array
     */
    public function lists($column, $key = null)
    {
        $select = is_null($key) ? [$column] : [$column, $key];

        if (!is_null($this->cacheMinutes)) {
            $results = $this->getCached($select);
        }
        else {
            $results = $this->getFresh($select);
        }

        $collection = new Collection($results);

        return $collection->lists($column, $key);
    }

    /**
     * getFresh executes the query as a fresh "select" statement
     * @param  array  $columns
     * @return \October\Rain\Halcyon\Collection|static[]
     */
    public function getFresh($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $processCmd = $this->selectSingle ? 'processSelectOne' : 'processSelect';

        return $this->processor->{$processCmd}($this, $this->runSelect());
    }

    /**
     * runSelect runs the query as a "select" statement against the datasource
     * @return array
     */
    protected function runSelect()
    {
        if (!is_string($this->from)) {
            throw new ApplicationException(sprintf("The from property is invalid, make sure that %s has a string value for its \$dirName property (use '' if not using directories)", get_class($this->model)));
        }

        $this->validateDirectoryName($this->from);

        if ($this->selectSingle) {
            list($name, $extension) = $this->selectSingle;

            $this->validateFileName($name . '.' . $extension);

            return $this->datasource->selectOne($this->from, $name, $extension);
        }

        return $this->datasource->select($this->from, [
            'columns' => $this->columns,
            'extensions' => $this->extensions
        ]);
    }

    /**
     * setModel instance for the model being queried
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->extensions = $this->model->getAllowedExtensions();

        $this->from($this->model->getObjectTypeDirName());

        return $this;
    }

    /**
     * toCompiled gets the compiled file content representation of the query
     * @return string
     */
    public function toCompiled()
    {
        return $this->processor->processUpdate($this, []);
    }

    /**
     * insert a new record into the datasource.
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }

        $this->validateFileName();

        list($name, $extension) = $this->model->getFileNameParts();

        $result = $this->processor->processInsert($this, $values);

        return $this->datasource->insert(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result
        );
    }

    /**
     * update a record in the datasource.
     * @return int
     */
    public function update(array $values)
    {
        $this->validateFileName();

        list($name, $extension) = $this->model->getFileNameParts();

        $result = $this->processor->processUpdate($this, $values);

        $oldName = $oldExtension = null;

        if ($this->model->isDirty('fileName')) {
            list($oldName, $oldExtension) = $this->model->getFileNameParts(
                $this->model->getOriginal('fileName')
            );
        }

        return $this->datasource->update(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result,
            $oldName,
            $oldExtension
        );
    }

    /**
     * delete a record from the database.
     * @param  string  $fileName
     * @return int
     */
    public function delete()
    {
        $this->validateFileName();

        list($name, $extension) = $this->model->getFileNameParts();

        return $this->datasource->delete(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * lastModified returns the last modified time of the object
     * @return int
     */
    public function lastModified()
    {
        $this->validateFileName();

        list($name, $extension) = $this->model->getFileNameParts();

        return $this->datasource->lastModified(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * getModels gets the hydrated models
     * @param  array  $results
     * @return \October\Rain\Halcyon\Model[]
     */
    public function getModels(array $results)
    {
        $datasource = $this->model->getDatasourceName();

        $models = $this->model->hydrate($results, $datasource);

        /*
         * Flag the models as loaded from cache, then reset the internal property.
         */
        if ($this->loadedFromCache) {
            $models->each(function ($model) {
                $model->setLoadedFromCache($this->loadedFromCache);
            });

            $this->loadedFromCache = false;
        }

        return $models->all();
    }

    /**
     * getModel instance being queried
     * @return \October\Rain\Halcyon\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    //
    // Validation (Hard)
    //

    /**
     * validateFileName validates the supplied filename, extension and path
     * @param string $fileName
     */
    protected function validateFileName($fileName = null)
    {
        if ($fileName === null) {
            $fileName = $this->model->fileName;
        }

        if (!strlen($fileName)) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        if (!$this->validateFileNamePath($fileName, $this->model->getMaxNesting())) {
            throw (new InvalidFileNameException)->setInvalidFileName($fileName);
        }

        $this->validateFileNameExtension($fileName, $this->model->getAllowedExtensions());

        return true;
    }

    /**
     * validateDirectoryName validates the supplied directory path
     * @param string $dirName
     */
    protected function validateDirectoryName($dirName = null)
    {
        if (!$this->validateFileNamePath($dirName, $this->model->getMaxNesting())) {
            throw (new InvalidDirectoryNameException)->setInvalidDirectoryName($dirName);
        }

        return true;
    }

    /**
     * validateFileNameExtension validates whether a file has an allowed extension
     * @param string $fileName Specifies a path to validate
     * @param array $allowedExtensions A list of allowed file extensions
     * @return void
     */
    protected function validateFileNameExtension($fileName, $allowedExtensions)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (strlen($extension) && !in_array($extension, $allowedExtensions)) {
            throw (new InvalidExtensionException)
                ->setInvalidExtension($extension)
                ->setAllowedExtensions($allowedExtensions)
            ;
        }
    }

    /**
     * validateFileNamePath validates a template path
     * Template directory and file names can contain only alphanumeric symbols, dashes and dots.
     * @param string $filePath Specifies a path to validate
     * @param int $maxNesting Specifies the maximum allowed nesting level
     * @return void
     */
    protected function validateFileNamePath($filePath, $maxNesting = 5)
    {
        if (strpos($filePath, '..') !== false) {
            return false;
        }

        if (strpos($filePath, './') !== false || strpos($filePath, '//') !== false) {
            return false;
        }

        $segments = explode('/', $filePath);
        if ($maxNesting !== null && count($segments) > ($maxNesting + 1)) {
            return false;
        }

        foreach ($segments as $segment) {
            if (!$this->validateFileNamePattern($segment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * validateFileNamePattern validates a template file or directory name
     * template file names can contain only alphanumeric symbols, dashes, underscores and dots.
     * @param string $fileName Specifies a path to validate
     * @return boolean Returns true if the file name is valid. Otherwise returns false.
     */
    protected function validateFileNamePattern($fileName)
    {
        return preg_match('/^[a-z0-9\_\-\.\/]+$/i', $fileName) ? true : false;
    }

    //
    // Caching
    //

    /**
     * remember indicates that the query results should be cached
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        list($this->cacheMinutes, $this->cacheKey) = [$minutes, $key];

        return $this;
    }

    /**
     * rememberForever indicates that the query results should be cached forever
     * @param  string  $key
     * @return $this
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

    /**
     * cacheTags indicates that the results, if cached, should use the given cache tags
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;
        return $this;
    }

    /**
     * cacheDriver indicate that the results, if cached, should use the given cache driver
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;
        return $this;
    }

    /**
     * getCached executes the query as a cached "select" statement
     * @param  array  $columns
     * @return array
     */
    public function getCached($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $key = $this->getCacheKey();

        $minutes = $this->cacheMinutes;
        $cache = $this->getCache();
        $callback = $this->getCacheCallback($columns);
        $isNewCache = !$cache->has($key);

        // If the "minutes" value is less than zero, we will use that as the indicator
        // that the value should be remembered values should be stored indefinitely
        // and if we have minutes we will use the typical remember function here.
        if ($minutes < 0) {
            $result = $cache->rememberForever($key, $callback);
        }
        else {
            $expiresAt = now()->addMinutes($minutes);
            $result = $cache->remember($key, $expiresAt, $callback);
        }

        // If this is an old cache record, we can check if the cache has been busted
        // by comparing the modification times. If this is the case, forget the
        // cache and then prompt a recycle of the results.
        if (!$isNewCache && $this->isCacheBusted($result)) {
            $cache->forget($key);
            $isNewCache = true;

            if ($minutes < 0) {
                $result = $cache->rememberForever($key, $callback);
            }
            else {
                $expiresAt = now()->addMinutes($minutes);
                $result = $cache->remember($key, $expiresAt, $callback);
            }
        }

        $this->loadedFromCache = !$isNewCache;

        return $result;
    }

    /**
     * isCacheBusted returns true if the cache for the file is busted. This only applies
     * to single record selection
     * @param  array  $result
     * @return bool
     */
    protected function isCacheBusted($result)
    {
        if (!$this->selectSingle) {
            return false;
        }

        $mtime = $result ? array_get(reset($result), 'mtime') : null;

        list($name, $extension) = $this->selectSingle;

        $currentMtime = $this->datasource->lastModified(
            $this->from,
            $name,
            $extension
        );

        return $currentMtime !== $mtime;
    }

    /**
     * getCache object with tags assigned, if applicable
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = $this->model->getCacheManager()->driver($this->cacheDriver);

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * getCacheKey gets a unique cache key for the complete query
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey ?: $this->generateCacheKey();
    }

    /**
     * generateCacheKey for the query
     * @return string
     */
    public function generateCacheKey()
    {
        $payload = [];
        $payload[] = $this->selectSingle ? serialize($this->selectSingle) : '*';
        $payload[] = $this->orders ? serialize($this->orders) : '*';
        $payload[] = $this->columns ? serialize($this->columns) : '*';
        $payload[] = $this->fileMatch;
        $payload[] = $this->limit;
        $payload[] = $this->offset;

        return $this->from . $this->datasource->makeCacheKey(implode('-', $payload));
    }

    /**
     * getCacheCallback used when caching queries
     * @param  string  $fileName
     * @return \Closure
     */
    protected function getCacheCallback($columns)
    {
        return function () use ($columns) {
            return $this->processInitCacheData($this->getFresh($columns));
        };
    }

    /**
     * processInitCacheData initializes the cache data of each record
     * @param  array  $data
     * @return array
     */
    protected function processInitCacheData($data)
    {
        if ($data) {
            $model = get_class($this->model);

            foreach ($data as &$record) {
                $model::initCacheItem($record);
            }
        }

        return $data;
    }

    /**
     * __call handles dynamic method calls into the method
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $className = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
