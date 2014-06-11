<?php namespace October\Rain\Database\Traits;

use Input;
use October\Rain\Database\ModelException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

trait Validation
{
    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [];

    /**
     * @var array The array of custom error messages.
     */
    public $customMessages = [];

    /**
     * @var \Illuminate\Support\MessageBag The message bag instance containing validation error messages
     */
    public $validationErrors;

    /**
     * @var bool Makes the validation procedure throw an {@link October\Rain\Database\ModelException} instead of returning
     * false when validation fails.
     */
    public $throwOnValidation = true;

    /**
     * Boot the validation trait for this model.
     *
     * @return void
     */
    public static function bootValidation()
    {
        static::extend(function($model){
            $model->validationErrors = new MessageBag;
        });

        static::validating(function($model) {
            $model->fireEvent('model.beforeValidate');
            if ($model->methodExists('beforeValidate'))
                $model->beforeValidate();
        });

        static::validated(function($model) {
            $model->fireEvent('model.afterValidate');
            if ($model->methodExists('afterValidate'))
                $model->afterValidate();
        });
    }

    /**
     * Instantiates the validator used by the validation process, depending if the class is being used inside or
     * outside of Laravel.
     * @return \Illuminate\Validation\Validator
     */
    protected static function makeValidator($data, $rules, $customMessages) 
    {
        return Validator::make($data, $rules, $customMessages);
    }

    /**
     * Force save the model even if validation fails.
     * @return bool
     */
    public function forceSave(array $data = null, $sessionKey = null)
    {
        $this->sessionKey = $sessionKey;
        return $this->saveInternal($data, ['force' => true]);
    }

    /**
     * Validate the model instance
     * @return bool
     */
    public function validate($rules = null, $customMessages = null)
    {
        if ($this->fireModelEvent('validating') === false) {
            if ($this->throwOnValidation)
                throw new ModelException($this);
            else
                return false;
        }

        /*
         * Perform validation
         */
        $rules = (is_null($rules)) ? $this->rules : $rules;
        $rules = $this->processValidationRules($rules);
        $success = true;

        if (!empty($rules)) {
            $data = array_merge($this->getAttributes(), $this->getOriginalHashValues());
            $customMessages = is_null($customMessages) ? $this->customMessages : $customMessages;
            $validator = self::makeValidator($data, $rules, $customMessages);
            $success = $validator->passes();

            if ($success) {
                if ($this->validationErrors->count() > 0)
                    $this->validationErrors = new MessageBag;
            } else {
                $this->validationErrors = $validator->messages();
                if (Input::hasSession())
                    Input::flash();
            }
        }

        $this->fireModelEvent('validated', false);

        if (!$success && $this->throwOnValidation)
            throw new ModelException($this);

        return $success;
    }

    /**
     * Process rules
     */
    private function processValidationRules($rules)
    {
        foreach ($rules as $field => $ruleParts) {
            /*
             * Trim empty rules
             */
            if (is_string($ruleParts) && trim($ruleParts) == '') {
                unset($rules[$field]);
                continue;
            }

            /*
             * Normalize rulesets
             */
            if (!is_array($ruleParts))
                $ruleParts = explode('|', $ruleParts);

            /*
             * Analyse each rule individually
             */
            foreach ($ruleParts as $key => $rulePart) {
                /*
                 * Remove primary key unique validation rule if the model already exists
                 */
                if (starts_with($rulePart, 'unique') && $this->exists) {
                    $ruleParts[$key] = 'unique:'.$this->getTable().','.$field.','.$this->getKey();
                }
                /*
                 * Look for required:create and required:update rules
                 */
                else if (starts_with($rulePart, 'required:create') && $this->exists) {
                    unset($ruleParts[$key]);
                }
                else if (starts_with($rulePart, 'required:update') && !$this->exists) {
                    unset($ruleParts[$key]);
                }
            }

            $rules[$field] = $ruleParts;
        }

        return $rules;
    }

    /**
     * Get validation error message collection for the Model
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        return $this->validationErrors;
    }

    /**
     * Create a new native event for handling beforeValidate().
     * @param Closure|string $callback
     * @return void
     */
    public static function validating($callback)
    {
        static::registerModelEvent('validating', $callback);
    }

    /**
     * Create a new native event for handling afterValidate().
     * @param Closure|string $callback
     * @return void
     */
    public static function validated($callback)
    {
        static::registerModelEvent('validated', $callback);
    }

}