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
    public function selectOne($dirName, $fileName, $extension);

    /**
     * Returns all templates.
     *
     * @param  string  $dirName
     * @param  array   $options
     * @return array
     */
    public function select($dirName, array $options = []);

    /**
     * Creates a new template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @param  array   $content
     * @return bool
     */
    public function insert($dirName, $fileName, $extension, $content);

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
    public function update($dirName, $fileName, $extension, $content, $oldFileName = null, $oldExtension = null);

    /**
     * Run a delete statement against the datasource.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return int
     */
    public function delete($dirName, $fileName, $extension);

    /**
     * Return the last modified date of an object
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return int
     */
    public function lastModified($dirName, $fileName, $extension);

    /**
     * Generate a cache key unique to this datasource.
     *
     * @param  string  $name
     * @return string
     */
    public function makeCacheKey($name = '');

}
