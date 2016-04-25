<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

class MissingFileNameException extends RuntimeException
{
    /**
     * Name of the affected Halcyon model.
     *
     * @var string
     */
    protected $model;

    /**
     * Set the affected Halcyon model.
     *
     * @param  string   $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        $this->message = "No file name attribute (fileName) specified for model [{$model}].";

        return $this;
    }

    /**
     * Get the affected Halcyon model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
