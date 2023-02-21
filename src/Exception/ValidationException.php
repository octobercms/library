<?php namespace October\Rain\Exception;

use Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException as ValidationExceptionBase;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * ValidationException class
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ValidationException extends ValidationExceptionBase
{
    /**
     * @var array fields that are invalid
     */
    protected $fields;

    /**
     * @var array fieldPrefix
     */
    protected $fieldPrefix = [];

    /**
     * @var \Illuminate\Support\MessageBag errors in the form of a message bag
     */
    protected $errors;

    /**
     * __construct the validation exception.
     */
    public function __construct($validation)
    {
        parent::__construct($this->resolveToValidator($validation));

        $this->errors = $this->validator->errors();

        $this->evalErrors();
    }

    /**
     * resolveToValidator resolves general input for the validation exception
     * @param  mixed  $validation
     */
    protected function resolveToValidator($validation)
    {
        $validator = $validation;

        if (is_null($validation)) {
            $validator = ValidatorFacade::make([], []);
        }
        elseif (is_array($validation)) {
            $validator = ValidatorFacade::make([], []);
            $validator->errors()->merge($validation);
        }

        if (!$validator instanceof Validator) {
            throw new InvalidArgumentException('ValidationException constructor requires instance of Validator or array');
        }

        return $validator;
    }

    /**
     * evalErrors evaluates errors
     */
    protected function evalErrors()
    {
        $this->fields = [];

        foreach ($this->errors->getMessages() as $field => $messages) {
            $fieldName = implode('.', array_merge($this->fieldPrefix, [$field]));
            $this->fields[$fieldName] = (array) $messages;
        }

        $this->message = $this->errors->first();
    }

    /**
     * getErrors returns directly the message bag instance with the model's errors
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * getFields returns invalid fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * setFieldPrefix increases the field target specificity
     */
    public function setFieldPrefix(array $prefix)
    {
        $this->fieldPrefix = array_filter($prefix, 'strlen');

        $this->evalErrors();
    }
}
