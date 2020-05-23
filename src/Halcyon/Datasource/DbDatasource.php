<?php namespace October\Rain\Halcyon\Datasource;

use Db;
use Exception;
use Carbon\Carbon;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;

/**
 * Database based data source
 *
 * Table Structure:
 *  - id, unsigned integer
 *  - source, varchar
 *  - path, varchar
 *  - content, longText
 *  - file_size, unsigned integer // In bytes - NOTE: max file size of 4.29 GB represented with unsigned int in MySQL
 *  - updated_at, datetime
 *  - deleted_at, datetime, nullable
 */
class DbDatasource extends Datasource implements DatasourceInterface
{
    /**
     * @var string The identifier for this datasource instance
     */
    protected $source;

    /**
     * @var string The table name of the datasource
     */
    protected $table;

    /**
     * Create a new datasource instance.
     *
     * @param string $source The source identifier for this datasource instance
     * @param string $table The table for this database datasource
     * @return void
     */
    public function __construct(string $source, string $table)
    {
        $this->source = $source;

        $this->table = $table;

        $this->postProcessor = new Processor;
    }

    /**
     * Get the base QueryBuilder object
     */
    public function getBaseQuery()
    {
        return Db::table($this->table)->enableDuplicateCache();
    }

    /**
     * Get the QueryBuilder object
     *
     * @param bool $ignoreDeleted Flag to ignore deleted records, defaults to true
     * @return QueryBuilder
     */
    public function getQuery($ignoreDeleted = true)
    {
        $query = $this->getBaseQuery();

        $query->where('source', $this->source);

        if ($ignoreDeleted) {
            $query->whereNull('deleted_at');
        }

        /**
         * @event halcyon.datasource.db.extendQuery
         * Provides an opportunity to modify the query object used by the Halycon DbDatasource
         *
         * Example usage:
         *
         *     $datasource->bindEvent('halcyon.datasource.db.extendQuery', function ((QueryBuilder) $query, (bool) $ignoreDeleted) {
         *         // Apply a site filter in a multi-tenant application
         *         $query->where('site_id', SiteManager::getSite()->id);
         *     });
         *
         */
        $this->fireEvent('halcyon.datasource.db.extendQuery', [$query, $ignoreDeleted]);

        return $query;
    }

    /**
     * Helper to make file path.
     *
     * @param string $dirName
     * @param string $fileName
     * @param string $extension
     * @return string
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension)
    {
        return $dirName . '/' . $fileName . '.' . $extension;
    }

    /**
     * Returns a single template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return mixed
     */
    public function selectOne(string $dirName, string $fileName, string $extension)
    {
        $result = $this->getQuery()->where('path', $this->makeFilePath($dirName, $fileName, $extension))->first();

        if ($result) {
            return [
                'fileName' => $fileName . '.' . $extension,
                'content'  => $result->content,
                'mtime'    => Carbon::parse($result->updated_at)->timestamp,
                'record'   => $result,
            ];
        } else {
            return $result;
        }
    }

    /**
     * Returns all templates.
     *
     * @param  string  $dirName
     * @param array $options Array of options, [
     *                          'columns'    => ['fileName', 'mtime', 'content'], // Only return specific columns
     *                          'extensions' => ['htm', 'md', 'twig'],            // Extensions to search for
     *                          'fileMatch'  => '*gr[ae]y',                       // Shell matching pattern to match the filename against using the fnmatch function
     *                          'orders'     => false                             // Not implemented
     *                          'limit'      => false                             // Not implemented
     *                          'offset'     => false                             // Not implemented
     *                      ];
     * @return array
     */
    public function select(string $dirName, array $options = [])
    {
        // Initialize result set
        $result = [];

        // Prepare query options
        extract(array_merge([
            'columns'     => null,  // Only return specific columns (fileName, mtime, content)
            'extensions'  => null,  // Match specified extensions
            'fileMatch'   => null,  // Match the file name using fnmatch()
            'orders'      => null,  // @todo
            'limit'       => null,  // @todo
            'offset'      => null   // @todo
        ], $options));

        if ($columns === ['*'] || !is_array($columns)) {
            $columns = null;
        }

        // Apply the dirName query
        $query = $this->getQuery()->where('path', 'like', $dirName . '%');

        // Apply the extensions filter
        if (is_array($extensions) && !empty($extensions)) {
            $query->where(function ($query) use ($extensions) {
                // Get the first extension to query for
                $query->where('path', 'like', '%' . '.' . array_pop($extensions));

                if (count($extensions)) {
                    foreach ($extensions as $ext) {
                        $query->orWhere('path', 'like', '%' . '.' . $ext);
                    }
                }
            });
        }

        // Retrieve the results
        $results = $query->get();

        foreach ($results as $item) {
            $resultItem = [];
            $fileName = ltrim(str_replace($dirName, '', $item->path), '/');

            // Apply the fileMatch filter
            if (!empty($fileMatch) && !fnmatch($fileMatch, $fileName)) {
                continue;
            }

            // Apply the columns filter on the data returned
            if (is_null($columns)) {
                $resultItem = [
                    'fileName' => $fileName,
                    'content'  => $item->content,
                    'mtime'    => Carbon::parse($item->updated_at)->timestamp,
                    'record'   => $item,
                ];
            } else {
                if (in_array('fileName', $columns)) {
                    $resultItem['fileName'] = $fileName;
                }

                if (in_array('content', $columns)) {
                    $resultItem['content'] = $item->content;
                }

                if (in_array('mtime', $columns)) {
                    $resultItem['mtime'] = Carbon::parse($item->updated_at)->timestamp;
                }

                if (in_array('record', $columns)) {
                    $resultItem['record'] = $item;
                }
            }

            $result[] = $resultItem;
        }

        return $result;
    }

    /**
     * Creates a new template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @param  string  $content
     * @return bool
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        // Check for an existing record
        if ($this->getQuery()->where('path', $path)->count() > 0) {
            throw (new FileExistsException())->setInvalidPath($path);
        }

        // Check for a deleted record, update it if it exists instead
        if ($this->getQuery(false)->where('path', $path)->first()) {
            return $this->update($dirName, $fileName, $extension, $content);
        }

        try {
            $record = [
                'source'     => $this->source,
                'path'       => $path,
                'content'    => $content,
                'file_size'  => mb_strlen($content, '8bit'),
                'updated_at' => Carbon::now()->toDateTimeString(),
                'deleted_at' => null,
            ];

            /**
             * @event halcyon.datasource.db.beforeInsert
             * Provides an opportunity to modify records before being inserted into the DB
             *
             * Example usage:
             *
             *     $datasource->bindEvent('halcyon.datasource.db.beforeInsert', function ((array) &$record) {
             *         // Attach a site id to every record in a multi-tenant application
             *         $record['site_id'] = SiteManager::getSite()->id;
             *     });
             *
             */
            $this->fireEvent('halcyon.datasource.db.beforeInsert', [&$record]);

            // Get a raw query without filters applied to it
            $this->getBaseQuery()->insert($record);

            return $record['file_size'];
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * Updates an existing template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @param  string  $content
     * @param  string  $oldFileName Defaults to null
     * @param  string  $oldExtension Defaults to null
     * @return int
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        // Check if this file has been renamed
        if (!is_null($oldFileName)) {
            $fileName = $oldFileName;
        }
        if (!is_null($oldExtension)) {
            $extension = $oldExtension;
        }

        $oldPath = $this->makeFilePath($dirName, $fileName, $extension);

        // Update the existing record
        try {
            $fileSize = mb_strlen($content, '8bit');

            $data = [
                'path'       => $path,
                'content'    => $content,
                'file_size'  => $fileSize,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'deleted_at' => null,
            ];

            /**
             * @event halcyon.datasource.db.beforeUpdate
             * Provides an opportunity to modify records before being updated into the DB
             *
             * Example usage:
             *
             *     $datasource->bindEvent('halcyon.datasource.db.beforeUpdate', function ((array) &$data) {
             *         // Attach a site id to every record in a multi-tenant application
             *         $data['site_id'] = SiteManager::getSite()->id;
             *     });
             *
             */
            $this->fireEvent('halcyon.datasource.db.beforeUpdate', [&$data]);

            $this->getQuery(false)->where('path', $oldPath)->update($data);

            return $fileSize;
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * Run a delete statement against the datasource.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return bool
     */
    public function delete(string $dirName, string $fileName, string $extension)
    {
        try {
            // Get the existing record
            $path = $this->makeFilePath($dirName, $fileName, $extension);
            $recordQuery = $this->getQuery()->where('path', $path);

            // Attempt to delete the existing record
            if ($this->forceDeleting) {
                $result = $recordQuery->delete();
            } else {
                $result = $recordQuery->update(['deleted_at' => Carbon::now()->toDateTimeString()]);
            }

            // Throw an exception if there were no records affected by this query
            if (!$result) {
                throw new Exception('No records were affected!');
            }

            return true;
        }
        catch (Exception $ex) {
            throw (new DeleteFileException)->setInvalidPath($path);
        }
    }

    /**
     * Return the last modified date of an object
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return int
     */
    public function lastModified(string $dirName, string $fileName, string $extension)
    {
        try {
            return Carbon::parse($this->getQuery()
                    ->where('path', $this->makeFilePath($dirName, $fileName, $extension))
                    ->first()->updated_at)->timestamp;
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Generate a cache key unique to this datasource.
     *
     * @param  string  $name
     * @return string
     */
    public function makeCacheKey($name = '')
    {
        return crc32($this->source . $name);
    }

    /**
     * Generate a paths cache key unique to this datasource
     *
     * @return string
     */
    public function getPathsCacheKey()
    {
        return 'halcyon-datastore-db-' . $this->table . '-' . $this->source;
    }

    /**
     * Get all available paths within this datastore
     *
     * @return array $paths ['path/to/file1.md' => true (path can be handled and exists), 'path/to/file2.md' => false (path can be handled but doesn't exist)]
     */
    public function getAvailablePaths()
    {
        /**
         * @event halcyon.datasource.db.beforeGetAvailablePaths
         * Halting event called before the cache of what paths are available in the DB is built
         *
         * Example usage:
         *
         *     $datasource->bindEvent('halcyon.datasource.db.beforeGetAvailablePaths', function () use ($datastore) {
         *         return ['path/to/file/that/exists' => true, 'path/to/file/that/is/deleted' => false];
         *     });
         *
         */
        if (!$pathsCache = $this->fireEvent('halcyon.datasource.db.beforeGetAvailablePaths', [], true)) {
            // Only query for what is required
            $this->bindEventOnce('halcyon.datasource.db.extendQuery', function ($query, $ignoreDeleted) {
                $query->addSelect('source', 'path', 'deleted_at');
            });

            // Get all records stored in the DB
            $records = $this->getQuery(false)->get();

            foreach ($records as $record) {
                $pathsCache[$record->path] = !$record->deleted_at;
            }
        }

        return (array) $pathsCache;
    }
}
