<?php namespace October\Rain\Halcyon\Exception;

use October\Rain\Halcyon\Model;
use October\Rain\Exception\ValidationException;

/**
 * ModelException used when validation fails, contains the invalid model for easy analysis
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class ModelException extends ValidationException
{
    /**
     * @var Model model
     */
    protected $model;

    /**
     * __construct receives the invalid model
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
