<?php namespace October\Rain\Halcyon\Datasource;

interface DatasourceInterface
{

    /**
     * Returns a single template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return mixed
     */
    public function selectOne(string $dirName, string $fileName, string $extension);

    /**
     * Returns all templates.
     *
     * @param  string  $dirName
     * @param  array   $options
     * @return array
     */
    public function select(string $dirName, array $options = []);

    /**
     * Creates a new template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @param  array   $content
     * @return bool
     */
    public function insert(string $dirName, string $fileName, string $extension, string $content);

    /**
     * Updates an existing template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @param  array   $content
     * @param  string  $oldFileName Defaults to null
     * @param  string  $oldExtension Defaults to null
     * @return int
     */
    public function update(string $dirName, string $fileName, string $extension, string $content, $oldFileName = null, $oldExtension = null);

    /**
     * Run a delete statement against the datasource.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return bool
     */
    public function delete(string $dirName, string $fileName, string $extension);

    /**
     * Run a delete statement against the datasource, forcing the complete removal of the template
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return bool
     */
    public function forceDelete(string $dirName, string $fileName, string $extension);

    /**
     * Return the last modified date of an object
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return int
     */
    public function lastModified(string $dirName, string $fileName, string $extension);

    /**
     * Generate a cache key unique to this datasource.
     *
     * @param  string  $name
     * @return string
     */
    public function makeCacheKey($name = '');

    /**
     * Generate a paths cache key unique to this datasource
     *
     * @return string
     */
    public function getPathsCacheKey();

    /**
     * Get all available paths within this datastore
     *
     * @return array $paths ['path/to/file1.md' => true (path can be handled and exists), 'path/to/file2.md' => false (path can be handled but doesn't exist)]
     */
    public function getAvailablePaths();
}
