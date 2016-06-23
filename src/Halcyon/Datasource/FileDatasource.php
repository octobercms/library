<?php namespace October\Rain\Halcyon\Datasource;

use Illuminate\Filesystem\Filesystem;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\CreateFileException;
use October\Rain\Halcyon\Exception\DeleteFileException;
use October\Rain\Halcyon\Exception\FileExistsException;
use October\Rain\Halcyon\Exception\CreateDirectoryException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * File based datasource.
 */
class FileDatasource extends Datasource implements DatasourceInterface
{

    /**
     * The local path where the datasource can be found.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new datasource instance.
     *
     * @param  string   $container
     * @param  array    $config
     * @return void
     */
    public function __construct($basePath, Filesystem $files)
    {
        $this->basePath = $basePath;

        $this->files = $files;

        $this->postProcessor = new Processor;
    }

    /**
     * Returns a single template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @return mixed
     */
    public function selectOne($dirName, $fileName, $extension)
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
     * Returns all templates.
     *
     * @param  string  $dirName
     * @return array
     */
    public function select($dirName, array $options = [])
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
        $it->setMaxDepth(1); // Support only a single level of subdirectories
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
                $fileName = basename($it->getPath()).'/'.$fileName;
            }

            /*
             * Filter by file name match
             */
            if ($fileMatch !== null && !fnmatch($fileName, $fileMatch)) {
                $it->next();
                continue;
            }

            $item = [];

            $path = $this->basePath . '/' . $dirName . '/' .$fileName;

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
     * Creates a new template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  array   $content
     * @return bool
     */
    public function insert($dirName, $fileName, $extension, $content)
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
     * Updates an existing template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  array   $content
     * @return int
     */
    public function update($dirName, $fileName, $extension, $content, $oldFileName = null, $oldExtension = null)
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
     * Run a delete statement against the datasource.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @return int
     */
    public function delete($dirName, $fileName, $extension)
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
     * Run a delete statement against the datasource.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @return int
     */
    public function lastModified($dirName, $fileName, $extension)
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
     * Ensure the requested file can be created in the requested directory.
     * @return void
     */
    protected function validateDirectoryForSave($dirName, $fileName, $extension)
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
     * Helper to make file path.
     * @return string
     */
    protected function makeFilePath($dirName, $fileName, $extension)
    {
        return $this->basePath . '/' . $dirName . '/' .$fileName . '.' . $extension;
    }

    /**
     * Generate a cache key unique to this datasource.
     * @return string
     */
    public function makeCacheKey($name = '')
    {
        return crc32($this->basePath . $name);
    }

    /**
     * Returns the base path for this datasource.
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

}