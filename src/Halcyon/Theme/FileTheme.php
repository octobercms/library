<?php namespace October\Rain\Halcyon\Theme;

use Illuminate\Filesystem\Filesystem;
use October\Rain\Halcyon\Processors\Processor;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * File based theme.
 */
class FileTheme implements ThemeInterface
{

    /**
     * The local path where the theme can be found.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The query post processor implementation.
     *
     * @var \October\Rain\Halcyon\Processors\Processor
     */
    protected $postProcessor;

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

            return $this->files->get($path);
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
            'fnMatch'    => null,
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
            if ($fnMatch !== null && !fnmatch($fileName, $fnMatch)) {
                $it->next();
                continue;
            }

            $filePath = $this->basePath . '/' . $dirName . '/' .$fileName;
            $result[$filePath] = $this->files->get($filePath);

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
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->put($path, $content);
        }
        catch (Exception $ex) {
            return null;
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
    public function update($dirName, $fileName, $extension, $content)
    {
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->put($path, $content);
        }
        catch (Exception $ex) {
            return null;
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
        try {
            $path = $this->makeFilePath($dirName, $fileName, $extension);

            return $this->files->delete($path);
        }
        catch (Exception $ex) {
            return null;
        }
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \October\Rain\Halcyon\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    protected function makeFilePath($dirName, $fileName, $extension)
    {
        return $this->basePath . '/' . $dirName . '/' .$fileName . '.' . $extension;
    }

}