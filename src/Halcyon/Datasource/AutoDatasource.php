<?php namespace October\Rain\Halcyon\Datasource;

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\DeleteFileException;
use Exception;
use Cache;

/**
 * AutoDatasource loads templates from multiple data sources
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class AutoDatasource extends Datasource implements DatasourceInterface
{
    /**
     * __construct create a new datasource instance
     */
    public function __construct(array $datasources)
    {
        $this->datasources = $datasources;

        $this->primaryDatasource = array_first($datasources);

        $this->postProcessor = new Processor;
    }

    /**
     * hasTemplate checks if a template is found in the datasource
     */
    public function hasTemplate(string $dirName, string $fileName, string $extension): bool
    {
        foreach ($this->datasources as $datasource) {
            if ($datasource->hasTemplate($dirName, $fileName, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * selectOne returns a single template
     */
    public function selectOne(string $dirName, string $fileName, string $extension)
    {
        foreach ($this->datasources as $source) {
            if (!$source->hasTemplate($dirName, $fileName, $extension)) {
                continue;
            }

            return $source->selectOne($dirName, $fileName, $extension);
        }

        return null;
    }

    /**
     * select returns all templates
     */
    public function select(string $dirName, array $options = []): array
    {
        $result = [];

        // Build from the ground up
        foreach (array_reverse($this->datasources) as $datasource) {
            $result = array_merge($result, $datasource->select($dirName, $options));
        }

        return collect($result)->keyBy('fileName')->all();
    }

    /**
     * insert creates a new template
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool
    {
        return $this->primaryDatasource->insert($dirName, $fileName, $extension, $content);
    }

    /**
     * update an existing template
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null): int
    {
        $findFileName = $oldFileName ?: $fileName;
        $findExt = $oldExtension ?: $extension;

        if ($this->primaryDatasource->selectOne($dirName, $findFileName, $findExt)) {
            $result = $this->primaryDatasource->update($dirName, $fileName, $extension, $content, $oldFileName, $oldExtension);
        }
        else {
            $result = $this->primaryDatasource->insert($dirName, $fileName, $extension, $content);
        }

        return $result;
    }

    /**
     * delete against the datasource
     */
    public function delete(string $dirName, string $fileName, string $extension): bool
    {
        return $this->primaryDatasource->delete($dirName, $fileName, $extension);
    }

    /**
     * forceDelete against the datasource, forcing the complete removal of the template
     */
    public function forceDelete(string $dirName, string $fileName, string $extension): bool
    {
        $result = false;

        foreach ($this->datasources as $datasource) {
            if ($datasource->delete($dirName, $fileName, $extension)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * lastModified returns the last modified date of an object
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int
    {
        foreach ($this->datasources as $source) {
            if (!$source->hasTemplate($dirName, $fileName, $extension)) {
                continue;
            }

            return $source->lastModified($dirName, $fileName, $extension);
        }

        return null;
    }

    /**
     * makeCacheKey unique to this datasource
     */
    public function makeCacheKey(string $name = ''): string
    {
        $key = '';

        foreach ($this->datasources as $datasource) {
            $key .= $datasource->makeCacheKey($name) . '-';
        }

        $key .= $name;

        return (string) crc32($key);
    }

    //
    // Models
    //

    /**
     * hasIndex returns true if the specified index exists in datasources
     */
    public function hasIndex(int $index)
    {
        return array_key_exists($index, $this->datasources);
    }

    /**
     * hasModelAtIndex
     */
    public function hasModelAtIndex($index, Model $model): bool
    {
        if (!$this->hasIndex($index)) {
            return false;
        }

        // Get the path parts
        $dirName = $model->getObjectTypeDirName();
        list($fileName, $extension) = $model->getFileNameParts();

        // Model doesn't exist
        if ($fileName === null) {
            return false;
        }

        return $this->datasources[$index]->hasTemplate($dirName, $fileName, $extension);
    }

    /**
     * updateModelAtIndex updates at a specific datasource index
     */
    public function updateModelAtIndex(int $index, Model $model): int
    {
        if (!$this->hasIndex($index)) {
            return 0;
        }

        // Get the path parts
        $dirName = $model->getObjectTypeDirName();
        list($fileName, $extension) = $model->getFileNameParts();

        $datasource = $this->datasources[$index];

        // Get the file content
        $content = $datasource->getPostProcessor()->processUpdate($model->newQuery(), []);

        return $datasource->update($dirName, $fileName, $extension, $content);
    }

    /**
     * deleteModelAtIndex against a specific datasource index
     */
    public function forceDeleteModelAtIndex(int $index, Model $model): bool
    {
        if (!$this->hasIndex($index)) {
            return false;
        }

        // Get the path parts
        $dirName = $model->getObjectTypeDirName();
        list($fileName, $extension) = $model->getFileNameParts();

        return $this->datasources[$index]->forceDelete($dirName, $fileName, $extension);
    }
}
