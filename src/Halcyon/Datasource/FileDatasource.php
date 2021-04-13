<?php namespace October\Rain\Halcyon\Datasource;

use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;
use October\Rain\Halcyon\Exception\CreateDirectoryException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * FileDatasource
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class FileDatasource extends Datasource implements DatasourceInterface
{
    /**
     * @var string basePath is a local path to find the datasource
     */
    protected $basePath;

    /**
     * @var \October\Rain\Filesystem\Filesystem
     */
    protected $files;

    /**
     * __construct a new datasource instance
     */
    public function __construct(string $basePath, Filesystem $files)
    {
        $this->basePath = $basePath;

        $this->files = $files;

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
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return [
                'fileName' => $fileName . '.' . $extension,
                'content'  => $this->files->get($path),
                'mtime'    => $this->files->lastModified($path)
            ];
        }
        catch (Exception $ex) {
            return null;
        }
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
        extract(array_merge([
            'columns'     => null,  // Only return specific columns (fileName, mtime, content)
            'extensions'  => null,  // Match specified extensions
            'fileMatch'   => null,  // Match the file name using fnmatch()
            'orders'      => null,  // @todo
            'limit'       => null,  // @todo
            'offset'      => null   // @todo
        ], $options));

        $result = [];
        $dirPath = $this->basePath . '/' . $dirName;

        if (!$this->files->isDirectory($dirPath)) {
            return $result;
        }

        if ($columns === ['*'] || !is_array($columns)) {
            $columns = null;
        }
        else {
            $columns = array_flip($columns);
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
        // @todo This should come from $maxNesting defined in the model -sg
        $it->setMaxDepth(5);
        $it->rewind();

        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }

            /*
             * Filter by extension
             */
            $fileExt = $it->getExtension();
            if ($extensions !== null && !in_array($fileExt, $extensions)) {
                $it->next();
                continue;
            }

            $fileName = $it->getBasename();
            if ($it->getDepth() > 0) {
                $baseName = $this->files->normalizePath(substr($it->getPath(), strlen($dirPath) + 1));
                $fileName = $baseName . '/' . $fileName;
            }

            /*
             * Filter by file name match
             */
            if ($fileMatch !== null && !fnmatch($fileMatch, $fileName)) {
                $it->next();
                continue;
            }

            $item = [];

            $path = $this->basePath . '/' . $dirName . '/' . $fileName;

            $item['fileName'] = $fileName;

            if (!$columns || array_key_exists('content', $columns)) {
                $item['content'] = $this->files->get($path);
            }

            if (!$columns || array_key_exists('mtime', $columns)) {
                $item['mtime'] = $this->files->lastModified($path);
            }

            $result[] = $item;

            $it->next();
        }

        return $result;
    }

    /**
     * insert creates a new template
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool
    {
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        if ($this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        try {
            return $this->files->put($path, $content);
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
        $this->validateDirectoryForSave($dirName, $fileName, $extension);

        $path = $this->makeFilePath($dirName, $fileName, $extension);

        /*
         * The same file is safe to rename when the case is changed
         * eg: FooBar -> foobar
         */
        $iFileChanged = ($oldFileName !== null && strcasecmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null && strcasecmp($oldExtension, $extension) !== 0);

        if ($iFileChanged && $this->files->isFile($path)) {
            throw (new FileExistsException)->setInvalidPath($path);
        }

        /*
         * File to be renamed, as delete and recreate
         */
        $fileChanged = ($oldFileName !== null && strcmp($oldFileName, $fileName) !== 0) ||
            ($oldExtension !== null && strcmp($oldExtension, $extension) !== 0);

        if ($fileChanged) {
            $this->delete($dirName, $oldFileName, $oldExtension);
        }

        try {
            return $this->files->put($path, $content);
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
        $path = $this->makeFilePath($dirName, $fileName, $extension);

        try {
            return $this->files->delete($path);
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
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->lastModified($path);
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * validateDirectoryForSave ensures the requested file can be created in
     * the requested directory
     */
    protected function validateDirectoryForSave(string $dirName, string $fileName, string $extension)
    {
        $path = $this->makeFilePath($dirName, $fileName, $extension);
        $dirPath = $this->basePath . '/' . $dirName;

        /*
         * Create base directory
         */
        if (
            (!$this->files->exists($dirPath) || !$this->files->isDirectory($dirPath)) &&
            !$this->files->makeDirectory($dirPath, 0777, true, true)
        ) {
            throw (new CreateDirectoryException)->setInvalidPath($dirPath);
        }

        /*
         * Create base file directory
         */
        if (($pos = strpos($fileName, '/')) !== false) {
            $fileDirPath = dirname($path);

            if (
                !$this->files->isDirectory($fileDirPath) &&
                !$this->files->makeDirectory($fileDirPath, 0777, true, true)
            ) {
                throw (new CreateDirectoryException)->setInvalidPath($fileDirPath);
            }
        }
    }

    /**
     * makeFilePath helper to make file path
     */
    protected function makeFilePath(string $dirName, string $fileName, string $extension): string
    {
        return $this->basePath . '/' . $dirName . '/' .$fileName . '.' . $extension;
    }

    /**
     * getBasePath returns the base path for this datasource
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * makeCacheKey unique to this datasource
     */
    public function makeCacheKey(string $name = ''): string
    {
        return crc32($this->basePath . $name);
    }
}
