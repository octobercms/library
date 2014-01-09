<?php namespace October\Rain\Support;

use Illuminate\Validation\Validator;

/**
 * Validation exception class.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class ValidationException extends \Exception
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
    public function __construct(Validator $validator)
    {
        parent::__construct();
        $this->errors = $validator->messages();
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