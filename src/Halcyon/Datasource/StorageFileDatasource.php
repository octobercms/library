<?php namespace October\Rain\Halcyon\Datasource;

use Db;
use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;
use Carbon\Carbon;
use Exception;

/**
 * StorageFileDatasource stores file content on disk and metadata in the database
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class StorageFileDatasource extends Datasource implements SoftDeleteDatasourceInterface, ResolvableDatasourceInterface
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
     * @var string storagePath is the local root path for stored files
     */
    protected $storagePath;

    /**
     * @var Filesystem files
     */
    protected $files;

    /**
     * @var array pathCache
     */
    protected static $pathCache = [];

    /**
     * @var array|null mtimeCache
     */
    protected static $mtimeCache = [];

    /**
     * @var array trashedPathCache
     */
    protected static $trashedPathCache = [];

    /**
     * __construct a new datasource instance
     */
    public function __construct(string $source, string $table, string $storagePath, Filesystem $files)
    {
        $this->source = $source;
        $this->table = $table;
        $this->storagePath = rtrim($storagePath, '/\\');
        $this->files = $files;
        $this->postProcessor = new Processor;
    }

    /**
     * hasTemplate checks if a template is found in the datasource
     */
    public function hasTemplate(string $dirName, string $fileName, string $extension): bool
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if (!$this->hasActiveRecord($path)) {
            return false;
        }

        return $this->files->isFile($this->makeDiskPathFromPath($path));
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
            return null;
        }

        $diskPath = $this->makeDiskPathFromPath($path);

        if (!$this->files->isFile($diskPath)) {
            return null;
        }

        return [
            'fileName' => $fileName . '.' . $extension,
            'content' => $this->files->get($diskPath),
            'mtime' => Carbon::parse($result->updated_at)->timestamp,
            'record' => $result
        ];
    }

    /**
     * select returns all templates, with available options:
     *
     * - columns: only return specific columns, eg: ['fileName', 'mtime', 'content']
     * - extensions: extensions to search for, eg: ['css', 'png']
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

        $results = $this->buildDirectoryQuery($dirName, $extensions)->get();

        foreach ($results as $item) {
            self::$pathCache[$this->source][$item->path] = $item;

            $resultItem = [];
            $fileName = $this->pathToFileName($dirName, $item->path);

            if (!$this->matchesFileMatch($fileMatch, $fileName)) {
                continue;
            }

            $diskPath = $this->makeDiskPathFromPath($item->path);

            if (!$this->files->isFile($diskPath)) {
                continue;
            }

            if ($columns === null) {
                $resultItem = [
                    'fileName' => $fileName,
                    'content' => $this->files->isFile($diskPath) ? $this->files->get($diskPath) : '',
                    'mtime' => Carbon::parse($item->updated_at)->timestamp,
                    'record' => $item,
                ];
            }
            else {
                if (in_array('fileName', $columns)) {
                    $resultItem['fileName'] = $fileName;
                }

                if (in_array('content', $columns) && $this->files->isFile($diskPath)) {
                    $resultItem['content'] = $this->files->get($diskPath);
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

        if ($this->getQuery(false)->where('path', $path)->first()) {
            return $this->update($dirName, $fileName, $extension, $content);
        }

        try {
            $diskPath = $this->makeDiskPath($dirName, $fileName, $extension);
            $this->ensureDirectoryExists(dirname($diskPath));
            $this->files->put($diskPath, $content);

            $fileSize = strlen($content);
            $record = [
                'source' => $this->source,
                'path' => $path,
                'content' => null,
                'file_size' => $fileSize,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'deleted_at' => null,
            ];

            $this->fireEvent('halcyon.datasource.storage.beforeInsert', [&$record]);

            $this->getBaseQuery()->insert($record);

            $this->flushCache();

            return $fileSize;
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

        if ($oldFileName !== null) {
            $fileName = $oldFileName;
        }
        if ($oldExtension !== null) {
            $extension = $oldExtension;
        }

        $oldPath = $this->makeFilePath($dirName, $fileName, $extension);

        try {
            $diskPath = $this->makeDiskPathFromPath($path);
            $this->ensureDirectoryExists(dirname($diskPath));
            $this->files->put($diskPath, $content);

            if ($oldPath !== $path && $this->files->isFile($oldDiskPath = $this->makeDiskPathFromPath($oldPath))) {
                $this->files->delete($oldDiskPath);
            }

            $fileSize = strlen($content);

            $data = [
                'path' => $path,
                'content' => null,
                'file_size' => $fileSize,
                'updated_at' => Carbon::now()->toDateTimeString(),
                'deleted_at' => null
            ];

            $this->fireEvent('halcyon.datasource.storage.beforeUpdate', [&$data]);

            $this->getQuery(false)->where('path', $oldPath)->update($data);

            $this->flushCache();

            return $fileSize;
        }
        catch (Exception $ex) {
            throw (new CreateFileException)->setInvalidPath($path);
        }
    }

    /**
     * tombstone creates a soft-deleted record for a path with no active record
     */
    public function tombstone(string $dirName, string $fileName, string $extension): bool
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if ($this->getQuery()->where('path', $path)->exists()) {
            return $this->delete($dirName, $fileName, $extension);
        }

        if ($this->isTemplateTrashed($dirName, $fileName, $extension)) {
            return true;
        }

        try {
            $now = Carbon::now()->toDateTimeString();

            $record = [
                'source' => $this->source,
                'path' => $path,
                'content' => null,
                'file_size' => 0,
                'updated_at' => $now,
                'deleted_at' => $now,
            ];

            $this->fireEvent('halcyon.datasource.storage.beforeInsert', [&$record]);

            $this->getBaseQuery()->insert($record);

            $this->flushCache();

            return true;
        }
        catch (Exception $ex) {
            throw (new DeleteFileException)->setInvalidPath($path);
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
                $diskPath = $this->makeDiskPathFromPath($path);
                if ($this->files->isFile($diskPath)) {
                    $this->files->delete($diskPath);
                }

                $result = $recordQuery->delete();
            }
            else {
                $result = $recordQuery->update(['deleted_at' => Carbon::now()->toDateTimeString()]);

                $diskPath = $this->makeDiskPathFromPath($path);
                if ($this->files->isFile($diskPath)) {
                    $this->files->delete($diskPath);
                }
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
     * isTemplateTrashed returns true when a soft-deleted record exists at the path
     */
    public function isTemplateTrashed(string $dirName, string $fileName, string $extension): bool
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        return isset($this->getTrashedPaths()[$path]);
    }

    /**
     * selectTrashedFileNames returns file names for soft-deleted templates in a directory
     */
    public function selectTrashedFileNames(string $dirName, array $options = []): array
    {
        extract(array_merge([
            'extensions' => null,
            'fileMatch' => null,
        ], $options));

        $fileNames = [];

        foreach (array_keys($this->getTrashedPaths()) as $path) {
            if (!$this->pathInDirectory($dirName, $path)) {
                continue;
            }

            if (!$this->matchesExtensionFilter($path, $extensions)) {
                continue;
            }

            $fileName = $this->pathToFileName($dirName, $path);

            if (!$this->matchesFileMatch($fileMatch, $fileName)) {
                continue;
            }

            $fileNames[] = $fileName;
        }

        return $fileNames;
    }

    /**
     * resolveLocalPath returns the absolute local path for a template, if available
     */
    public function resolveLocalPath(string $dirName, string $fileName, string $extension): ?string
    {
        if (!$this->hasTemplate($dirName, $fileName, $extension)) {
            return null;
        }

        return $this->makeDiskPath($dirName, $fileName, $extension);
    }

    /**
     * resolvePublicUrl returns a public URL for a template, if available
     */
    public function resolvePublicUrl(string $dirName, string $fileName, string $extension, array $context = []): ?string
    {
        if (!$this->hasTemplate($dirName, $fileName, $extension)) {
            return null;
        }

        $publicUrl = $context['publicUrl'] ?? null;
        if ($publicUrl) {
            $path = $this->makeFilePath($dirName, $fileName, $extension);
            return rtrim($publicUrl, '/') . '/' . ltrim($path, '/');
        }

        return null;
    }

    /**
     * getTrashedPaths returns cached tombstoned paths for this datasource source
     */
    protected function getTrashedPaths(): array
    {
        if (!isset(self::$trashedPathCache[$this->source])) {
            self::$trashedPathCache[$this->source] = array_fill_keys(
                $this->getQuery(false)->whereNotNull('deleted_at')->pluck('path')->all(),
                true
            );
        }

        return self::$trashedPathCache[$this->source];
    }

    /**
     * buildDirectoryQuery for active templates in a directory
     */
    protected function buildDirectoryQuery(string $dirName, ?array $extensions)
    {
        $query = $this->getQuery();

        if ($dirName !== '') {
            $query->where('path', 'like', $dirName . '/%');
        }

        if (is_array($extensions) && !empty($extensions)) {
            $this->applyExtensionsFilter($query, $extensions);
        }

        return $query;
    }

    /**
     * applyExtensionsFilter to a directory query
     */
    protected function applyExtensionsFilter($query, array $extensions)
    {
        $query->where(function ($query) use ($extensions) {
            $query->where('path', 'like', '%' . '.' . array_pop($extensions));

            if (count($extensions)) {
                foreach ($extensions as $ext) {
                    $query->orWhere('path', 'like', '%' . '.' . $ext);
                }
            }
        });
    }

    /**
     * hasActiveRecord checks if an active metadata record exists for a path
     */
    protected function hasActiveRecord(string $path): bool
    {
        if (isset(self::$pathCache[$this->source][$path])) {
            return true;
        }

        if (!isset(self::$mtimeCache[$this->source])) {
            self::$mtimeCache[$this->source] = $this->getQuery()->pluck('updated_at', 'path')->all();
        }

        return isset(self::$mtimeCache[$this->source][$path]);
    }

    /**
     * pathInDirectory checks if a stored path belongs to a directory
     */
    protected function pathInDirectory(string $dirName, string $path): bool
    {
        if ($dirName === '') {
            return true;
        }

        return $path === $dirName || str_starts_with($path, $dirName . '/');
    }

    /**
     * pathToFileName converts a stored path to a file name within a directory
     */
    protected function pathToFileName(string $dirName, string $path): string
    {
        return ltrim(str_replace($dirName, '', $path), '/');
    }

    /**
     * matchesFileMatch checks a file name against an optional fnmatch pattern
     */
    protected function matchesFileMatch(?string $fileMatch, string $fileName): bool
    {
        return empty($fileMatch) || fnmatch($fileMatch, $fileName);
    }

    /**
     * matchesExtensionFilter checks a stored path against optional extensions
     */
    protected function matchesExtensionFilter(string $path, ?array $extensions): bool
    {
        if (!is_array($extensions) || empty($extensions)) {
            return true;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array($extension, $extensions);
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
        $query->whereNull('content');

        if ($withTrashed) {
            $query->whereNull('deleted_at');
        }

        $this->fireEvent('halcyon.datasource.storage.extendQuery', [$query, $withTrashed]);

        return $query;
    }

    /**
     * makeFilePath helper to make file path relative to the theme root
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension): string
    {
        return $dirName . '/' . $fileName . '.' . $extension;
    }

    /**
     * makeDiskPath returns the absolute disk path for a template
     */
    protected function makeDiskPath(string $dirName, string $fileName, string $extension): string
    {
        return $this->makeDiskPathFromPath($this->makeFilePath($dirName, $fileName, $extension));
    }

    /**
     * makeDiskPathFromPath returns the absolute disk path from a relative path
     */
    protected function makeDiskPathFromPath(string $path): string
    {
        return $this->storagePath . '/' . $path;
    }

    /**
     * ensureDirectoryExists for a given path
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true, true);
        }
    }

    /**
     * flushCache
     */
    protected function flushCache()
    {
        unset(self::$pathCache[$this->source]);
        unset(self::$mtimeCache[$this->source]);
        unset(self::$trashedPathCache[$this->source]);
    }
}
