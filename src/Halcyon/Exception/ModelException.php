<?php namespace October\Rain\Halcyon\Exception;

use October\Rain\Halcyon\Model;
use October\Rain\Exception\ValidationException;
use Illuminate\Support\MessageBag;
use Exception;

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
     * @var MessageBag validationErrors
     */
    protected $validationErrors;

    /**
     * __construct receives the invalid model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->validationErrors = $model->errors();

        // Bypass parent constructor to avoid Validator facade dependency
        Exception::__construct($this->validationErrors->first());

        $this->evalErrors();
    }

    /**
     * errors returns validation errors
     */
    public function errors(): array
    {
        return $this->validationErrors->messages();
    }

    /**
     * getErrors returns the message bag instance
     */
    public function getErrors(): MessageBag
    {
        return $this->validationErrors;
    }

    /**
     * getModel returns the model with invalid attributes
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
