<?php namespace October\Rain\Halcyon\Datasource;

/**
 * DatasourceInterface
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
interface DatasourceInterface
{
    /**
     * hasTemplate checks if a template is found in the datasource
     */
    public function hasTemplate(string $dirName, string $fileName, string $extension): bool;

    /**
     * selectOne returns a single template
     */
    public function selectOne(string $dirName, string $fileName, string $extension);

    /**
     * select returns all templates
     */
    public function select(string $dirName, array $options = []): array;

    /**
     * insert creates a new template
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content): bool;

    /**
     * update an existing template
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null): int;

    /**
     * delete against the datasource
     */
    public function delete(string $dirName, string $fileName, string $extension): bool;

    /**
     * forceDelete against the datasource, forcing the complete removal of the template
     */
    public function forceDelete(string $dirName, string $fileName, string $extension): bool;

    /**
     * lastModified returns the last modified date of an object
     */
    public function lastModified(string $dirName, string $fileName, string $extension): ?int;

    /**
     * makeCacheKey unique to this datasource
     */
    public function makeCacheKey(string $name = ''): string;
}
