<?php namespace October\Rain\Halcyon\Theme;

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
 * File based theme.
 */
class FileTheme extends Theme implements ThemeInterface
{

    /**
     * The local path where the theme can be found.
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
     * Create a new theme instance.
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

            return [$this->files->lastModified($path), $this->files->get($path)];
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
            'extensions' => null,
            'fileMatch'  => null,
            'orders'     => null, // @todo
            'limit'      => null, // @todo
            'offset'     => null  // @todo
        ], $options));

        $result = [];
        $dirPath = $this->basePath . '/' . $dirName;

        if (!$this->files->isDirectory($dirPath)) {
            return $result;
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

            $path = $this->basePath . '/' . $dirName . '/' .$fileName;
            $result[$fileName] = [$this->files->lastModified($path), $this->files->get($path)];

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
         * File to be renamed, as delete and recreate
         */
        if ($oldFileName !== null && $oldFileName !== $fileName) {
            if ($this->files->isFile($path)) {
                throw (new FileExistsException)->setInvalidPath($path);
            }

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
     * Run a delete statement against the theme.
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
     * Run a delete statement against the theme.
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
     * Generate a cache key unique to this theme.
     * @return string
     */
    public function makeCacheKey($name)
    {
        return crc32($this->basePath . $name);
    }

}