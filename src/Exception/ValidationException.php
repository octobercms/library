<?php namespace October\Rain\Exception;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
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

        if (is_null($validation))
            return;

        if ($validation instanceof Validator)
            $this->errors = $validation->messages();
        else
            $this->errors = $this->makeErrors($validation);

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
     * Creates a new MessageBag object from supplied array.
     */
    public function makeErrors($fields)
    {
        if (!is_array($fields))
            $fields = [];

        $errors = new MessageBag;

        foreach ($fields as $field => $message)
            $errors->add($field, $message);

        return $errors;
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