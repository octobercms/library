<?php namespace October\Rain\Exception;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Exception;

/**
 * ValidationException class
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ValidationException extends Exception
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
     * __construct
     */
    public function __construct($validation)
    {
        parent::__construct();

        if (is_null($validation)) {
            $this->errors = new MessageBag([]);
        }
        elseif ($validation instanceof Validator) {
            $this->errors = $validation->messages();
        }
        elseif (is_array($validation)) {
            $this->errors = new MessageBag($validation);
        }
        else {
            throw new InvalidArgumentException('ValidationException constructor requires instance of Validator or array');
        }

        $this->evalErrors();
    }

    /**
     * evalErrors evaluates errors
     */
    protected function evalErrors()
    {
        $this->fields = [];

        foreach ($this->errors->getMessages() as $field => $messages) {
            $fieldName = implode('][', array_merge($this->fieldPrefix, [$field]));
            $this->fields[$fieldName] = $messages;
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
        $this->fieldPrefix = array_filter($prefix);

        $this->evalErrors();
    }
}
