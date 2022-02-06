<?php namespace October\Rain\Halcyon\Datasource;

use Db;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;
use Carbon\Carbon;
use Exception;

/**
 * DbDatasource looks at the database for templates
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class DbDatasource extends Datasource implements DatasourceInterface
{
    /**
     * @var string source identifier for this datasource instance
     */
    protected $source;

    /**
     * @var string table name of the datasource
     */
    protected $table;

    /**
     * @var array pathCache
     */
    protected static $pathCache = [];

    /**
     * @var array|null mtimeCache
     */
    protected static $mtimeCache = [];

    /**
     * __construct a new datasource instance
     */
    public function __construct(string $source, string $table)
    {
        $this->source = $source;

        $this->table = $table;

        $this->postProcessor = new Processor;
    }

    /**
     * hasTemplate checks if a template is found in the datasource
     */
    public function hasTemplate(string $dirName, string $fileName, string $extension): bool
    {
        return (bool) $this->lastModified($dirName, $fileName, $extension);
    }

    /**
     * selectOne returns a single template
     */
    public function selectOne(string $dirName, string $fileName, string $extension)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if (isset(self::$pathCache[$this->source][$path])) {
            $result = self::$pathCache[$this->source][$path];
        }
        else {
            $result = $this->getQuery()->where('path', $path)->first();
        }

        if (!$result) {
            return $result;
        }

        return [
            'fileName' => $fileName . '.' . $extension,
            'content' => $result->content,
            'mtime' => Carbon::parse($result->updated_at)->timestamp,
            'record' => $result
        ];
    }

    /**
     * select returns all templates, with availableoptions:
     *
     * - columns: only return specific columns, eg: ['fileName', 'mtime', 'content']
     * - extensions: extensions to search for, eg: ['htm', 'md', 'twig']
     * - fileMatch: pattern to match the filename against using the fnmatch function, eg: *gr[ae]y
     */
    public function select(string $dirName, array $options = []): array
    {
        $result = [];

        extract(array_merge([
            'columns' => null,
            'extensions' => null,
            'fileMatch' => null,
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
            self::$pathCache[$this->source][$item->path] = $item;

            $resultItem = [];
            $fileName = ltrim(str_replace($dirName, '', $item->path), '/');

            // Apply the fileMatch filter
            if (!empty($fileMatch) && !fnmatch($fileMatch, $fileName)) {
                continue;
            }

            // Apply the columns filter on the data returned
            if ($columns === null) {
                $resultItem = [
                    'fileName' => $fileName,
                    'content' => $item->content,
                    'mtime' => Carbon::parse($item->updated_at)->timestamp,
                    'record' => $item,
                ];
            }
            else {
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
     * insert creates a new template
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if ($this->getQuery()->where('path', $path)->count() > 0) {
            throw (new FileExistsException())->setInvalidPath($path);
        }

        // Update a trashed record
        if ($this->getQuery(false)->where('path', $path)->first()) {
            return $this->update($dirName, $fileName, $extension, $content);
        }

        try {
            $record = [
                'source' => $this->source,
                'path' => $path,
                'content' => $content,
                'file_size' => mb_strlen($content, '8bit'),
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

            $this->getBaseQuery()->insert($record);

            $this->flushCache();

            return $record['file_size'];
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * update an existing template
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null): int
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        // Check if this file has been renamed
        if ($oldFileName !== null) {
            $fileName = $oldFileName;
        }
        if ($oldExtension !== null) {
            $extension = $oldExtension;
        }

        $oldPath = $this->makeFilePath($dirName, $fileName, $extension);

        try {
            $fileSize = mb_strlen($content, '8bit');

            $data = [
                'path' => $path,
                'content' => $content,
                'file_size' => $fileSize,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'deleted_at' => null
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

            $this->flushCache();

            return $fileSize;
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * delete against the datasource
     */
    public function delete(string $dirName, string $fileName, string $extension): bool
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);
            $recordQuery = $this->getQuery()->where('path', $path);

            if ($this->forceDeleting) {
                $result = $recordQuery->delete();
            }
            else {
                $result = $recordQuery->update(['deleted_at' => Carbon::now()->toDateTimeString()]);
            }

            $this->flushCache();

            return (bool) $result;
        }
        catch (Exception $ex) {
            throw (new DeleteFileException)->setInvalidPath($path);
        }
    }

    /**
     * lastModified date of an object
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int
    {
        try {
            if (!isset(self::$mtimeCache[$this->source])) {
                self::$mtimeCache[$this->source] = $this->getQuery()->pluck('updated_at', 'path')->all();
            }

            $path = $this->makeFilePath($dirName, $fileName, $extension);
            if (!isset(self::$mtimeCache[$this->source][$path])) {
                return null;
            }

            $result = self::$mtimeCache[$this->source][$path];
            return Carbon::parse($result)->timestamp;
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * makeCacheKey unique to this datasource
     */
    public function makeCacheKey(string $name = ''): string
    {
        return (string) crc32($this->source . $name);
    }

    /**
     * getBaseQuery builder object
     */
    protected function getBaseQuery()
    {
        return Db::table($this->table);
    }

    /**
     * getQuery object
     */
    protected function getQuery(bool $withTrashed = true)
    {
        $query = $this->getBaseQuery();
        $query->where('source', $this->source);

        if ($withTrashed) {
            $query->whereNull('deleted_at');
        }

        /**
         * @event halcyon.datasource.db.extendQuery
         * Provides an opportunity to modify the query object used by the Halycon DbDatasource
         *
         * Example usage:
         *
         *     $datasource->bindEvent('halcyon.datasource.db.extendQuery', function ((QueryBuilder) $query, (bool) $withTrashed) {
         *         // Apply a site filter in a multi-tenant application
         *         $query->where('site_id', SiteManager::getSite()->id);
         *     });
         *
         */
        $this->fireEvent('halcyon.datasource.db.extendQuery', [$query, $withTrashed]);

        return $query;
    }

    /**
     * makeFilePath helper to make file path
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension): string
    {
        return $dirName . '/' . $fileName . '.' . $extension;
    }

    /**
     * flushCache
     */
    protected function flushCache()
    {
        unset(self::$pathCache[$this->source]);
        unset(self::$mtimeCache[$this->source]);
    }
}
