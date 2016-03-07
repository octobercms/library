<?php namespace October\Rain\Halcyon\Query;

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Theme\ThemeInterface;
use October\Rain\Halcyon\Processors\Processor;
use October\Rain\Halcyon\Exception\MissingFileNameException;

class Builder
{
    /**
     * The theme instance.
     *
     * @var \October\Rain\Halcyon\Theme\ThemeInterface
     */
    protected $theme;

    /**
     * The model being queried.
     *
     * @var \October\Rain\Halcyon\Model
     */
    protected $model;

    /**
     * The datasource query post processor instance.
     *
     * @var \October\Rain\Halcyon\Processors\Processor
     */
    protected $processor;

    /**
     * Create a new query builder instance.
     *
     * @param  \October\Rain\Halcyon\Theme\ThemeInterface  $theme
     * @param  \October\Rain\Halcyon\Model  $model
     * @param  \October\Rain\Halcyon\Processors\Processor  $processor
     * @return void
     */
    public function __construct(ThemeInterface $theme, Model $model, Processor $processor)
    {
        $this->theme = $theme;
        $this->model = $model;
        $this->processor = $processor;
    }

    /**
     * Find a single template by its file name.
     *
     * @param  string $fileName
     * @return mixed|static
     */
    public function find($fileName)
    {
        list($name, $extension) = $this->model->getFileNameParts($fileName);
        $fileName = $name . '.' . $extension; // Normalize file name

        $result = $this->theme->selectOne(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );

        $result = $this->processor->processSelectOne($this, $result, $fileName);

        if ($result === null) {
            return null;
        }

        $results = $this->getModels([$result]);

        return count($results) > 0 ? reset($results) : null;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return \October\Rain\Halcyon\Collection|static[]
     */
    public function get()
    {
        $results = $this->theme->select(
            $this->model->getObjectTypeDirName(),
            $this->model->getAllowedExtensions()
        );

        $results = $this->processor->processSelect($this, $results);

        $models = $this->getModels($results);

        return $this->model->newCollection($models);
    }

    /**
     * Insert a new record into the datasource.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return true;
        }

        if (!$fileName = $this->model->fileName) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        $result = $this->processor->processInsert($this, $values);

        return $this->theme->insert(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result
        );
    }

    /**
     * Update a record in the datasource.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        if (!$fileName = $this->model->fileName) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        $result = $this->processor->processUpdate($this, $values);

        $oldName = $oldExtension = null;

        if ($this->model->isDirty('fileName')) {
            list($oldName, $oldExtension) = $this->model->getFileNameParts(
                $this->model->getOriginal('fileName')
            );
        }

        return $this->theme->update(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension,
            $result,
            $oldName,
            $oldExtension
        );
    }

    /**
     * Delete a record from the database.
     *
     * @param  string  $fileName
     * @return int
     */
    public function delete($fileName = null)
    {
        if ($fileName === null && (!$fileName = $this->model->fileName)) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        list($name, $extension) = $this->model->getFileNameParts($fileName);

        return $this->theme->delete(
            $this->model->getObjectTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * Get the hydrated models.
     *
     * @param  array  $columns
     * @return \October\Rain\Halcyon\Model[]
     */
    public function getModels(array $results)
    {
        $theme = $this->model->getThemeName();

        return $this->model->hydrate($results, $theme)->all();
    }

    /**
     * Get the model instance being queried.
     *
     * @return \October\Rain\Halcyon\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
     * @return $this
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

}
