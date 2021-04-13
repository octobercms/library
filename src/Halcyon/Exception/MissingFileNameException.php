<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * MissingFileNameException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class MissingFileNameException extends RuntimeException
{
    /**
     * @var string model name
     */
    protected $model;

    /**
     * setModel sets the affected Halcyon model
     */
    public function setModel(string $model): MissingFileNameException
    {
        $this->model = $model;

        $this->message = "No file name attribute (fileName) specified for model [{$model}].";

        return $this;
    }

    /**
     * getModel gets the affected Halcyon model
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
