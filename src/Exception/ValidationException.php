<?php namespace October\Rain\Exception;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Exception;

/**
 * Validation exception class.
 *
 * @package october\exception
 * @author Alexey Bobkov, Samuel Georges
 */
class ValidationException extends Exception
{

    /**
     * @var array Collection of invalid fields.
     */
    protected $fields;

    /**
     * @var \Illuminate\Support\MessageBag The message bag instance containing validation error messages
     */
    protected $errors;

    /**
     * Constructor.
     */
    public function __construct($validation)
    {
        parent::__construct();

        if (is_null($validation)) {
            return;
        }

        if ($validation instanceof Validator) {
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
     * Evaluate errors.
     */
    protected function evalErrors()
    {
        foreach ($this->errors->getMessages() as $field => $messages) {
            $this->fields[$field] = $messages;
        }

        $this->message = $this->errors->first();
    }

    /**
     * Returns directly the message bag instance with the model's errors.
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns invalid fields.
     */
    public function getFields()
    {
        return $this->fields;
    }
}
