<?php namespace October\Rain\Halcyon\Theme;

use Closure;

interface ThemeInterface
{

    /**
     * Returns a single template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @return mixed
     */
    public function selectOne($dirName, $fileName, $extension);

    /**
     * Returns all templates.
     *
     * @param  string  $dirName
     * @return array
     */
    public function select($dirName, array $options = []);

    /**
     * Creates a new template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  array   $content
     * @return bool
     */
    public function insert($dirName, $fileName, $extension, $content);

    /**
     * Updates an existing template.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  array   $content
     * @return int
     */
    public function update($dirName, $fileName, $extension, $content);

    /**
     * Run a delete statement against the theme.
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @return int
     */
    public function delete($dirName, $fileName, $extension);

}
