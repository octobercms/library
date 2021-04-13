<?php namespace October\Rain\Halcyon\Datasource;

use Db;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;
use Carbon\Carbon;
use Exception;

/**
 * DbDatasource
 *
 * Table Structure:
 *  - id, unsigned integer
 *  - source, varchar
 *  - path, varchar
 *  - content, longText
 *  - file_size, unsigned integer (in bytes w/ max 4.29gb)
 *  - updated_at, datetime
 *  - deleted_at, datetime, nullable
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
        return (bool) $this->selectOne($dirName, $fileName, $extension);
    }

    /**
     * selectOne returns a single template
     */
    public function selectOne(string $dirName, string $fileName, string $extension)
    {
        $result = $this->getQuery()
            ->addSelect('content')
            ->where('path', $this->makeFilePath($dirName, $fileName, $extension))
            ->first()
        ;

        if (!$result) {
            return $result;
        }

        return [
            'fileName' => $fileName . '.' . $extension,
            'content'  => $result->content,
            'mtime'    => Carbon::parse($result->updated_at)->timestamp,
            'record'   => $result
        ];
    }

    /**
     * select returns all templates
     *
     * Available options:
     * [
     *     'columns'    => ['fileName', 'mtime', 'content'], // Only return specific columns
     *     'extensions' => ['htm', 'md', 'twig'],            // Extensions to search for
     *     'fileMatch'  => '*gr[ae]y',                       // Shell matching pattern to match the filename against using the fnmatch function
     *     'orders'     => false                             // Not implemented
     *     'limit'      => false                             // Not implemented
     *     'offset'     => false                             // Not implemented
     * ];
     */
    public function select(string $dirName, array $options = []): array
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

        // Apply the columns filter on the query
        if ($columns !== null) {
            // Source required for the datasource filtering, path required for actually using data
            $selects = ['source', 'path'];

            if (in_array('content', $columns)) {
                $selects[] = 'content';
            }

            if (in_array('mtime', $columns)) {
                $selects[] = 'updated_at';
            }

            $query->addSelect(...$selects);
        }
        else {
            $query->addSelect('content');
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
            if ($columns === null) {
                $resultItem = [
                    'fileName' => $fileName,
                    'content'  => $item->content,
                    'mtime'    => Carbon::parse($item->updated_at)->timestamp,
                    'record'   => $item,
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

        // Update the existing record
        try {
            $fileSize = mb_strlen($content, '8bit');

            $data = [
                'path'       => $path,
                'content'    => $content,
                'file_size'  => $fileSize,
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
            // Get the existing record
            $path = $this->makeFilePath($dirName, $fileName, $extension);
            $recordQuery = $this->getQuery()->where('path', $path);

            // Attempt to delete the existing record
            if ($this->forceDeleting) {
                $result = $recordQuery->delete();
            }
            else {
                $result = $recordQuery->update(['deleted_at' => Carbon::now()->toDateTimeString()]);
            }

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
            return Carbon::parse(
                $this->getQuery()
                    ->where('path', $this->makeFilePath($dirName, $fileName, $extension))
                    ->addSelect('updated_at')
                    ->first()
                    ->updated_at
            )->timestamp;
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
    protected function getQuery(bool $ignoreDeleted = true)
    {
        $query = $this->getBaseQuery();

        $query->addSelect('id', 'source', 'path', 'updated_at', 'file_size');
        $query->where('source', $this->source);

        if ($ignoreDeleted) {
            $query->addSelect('deleted_at');
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
         *         $query->addSelect('site_id');
         *         $query->where('site_id', SiteManager::getSite()->id);
         *     });
         *
         */
        $this->fireEvent('halcyon.datasource.db.extendQuery', [$query, $ignoreDeleted]);

        return $query;
    }

    /**
     * makeFilePath helper to make file path
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension): string
    {
        return $dirName . '/' . $fileName . '.' . $extension;
    }
}
