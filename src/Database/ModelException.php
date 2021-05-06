<?php namespace October\Rain\Database;

use October\Rain\Exception\ValidationException;

/**
 * ModelException is used when validation fails and contains the invalid model for easy analysis
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class ModelException extends ValidationException
{
    /**
     * @var Model model that is invalid
     */
    protected $model;

    /**
     * __construct recevies the troublesome model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->errors = $model->errors();
        $this->evalErrors();
    }

    /**
     * getModel returns the model with invalid attributes
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
