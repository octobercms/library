<?php namespace October\Rain\Halcyon\Traits;

use Validator;
use Illuminate\Support\MessageBag;
use October\Rain\Support\Facades\Input;
use October\Rain\Halcyon\Exception\ModelException;
use Exception;

trait Validation
{
    /**
     * @var array rules to be applied to the data.
     *
     * public $rules = [];
     */

    /**
     * @var array attributeNames of custom attributes
     *
     * public $attributeNames = [];
     */

    /**
     * @var array customMessages of custom error messages
     *
     * public $customMessages = [];
     */

    /**
     * @var bool throwOnValidation makes the validation procedure throw an {@link October\Rain\Database\ModelException}
     * instead of returning false when validation fails
     *
     * public $throwOnValidation = true;
     */

    /**
     * @var \Illuminate\Support\MessageBag validationErrors message bag
     */
    protected $validationErrors;

    /**
     * @var \Illuminate\Validation\Validator validator instance
     */
    protected static $validator;

    /**
     * bootValidation trait for this model.
     */
    public static function bootValidation()
    {
        if (!property_exists(static::class, 'rules')) {
            throw new Exception(sprintf('You must define a $rules property in %s to use the Validation trait.', static::class));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function ($data, $options) use ($model) {
                // If forcing the save event, the beforeValidate/afterValidate
                // events should still fire for consistency. So validate an
                // empty set of rules and messages.
                $force = array_get($options, 'force', false);
                if ($force) {
                    $valid = $model->validate([], []);
                }
                else {
                    $valid = $model->validate();
                }

                if (!$valid) {
                    return false;
                }
            }, 500);
        });
    }

    /**
     * getValidationAttributes returns the model data used for validation.
     * @return array
     */
    protected function getValidationAttributes()
    {
        return $this->getAttributes();
    }

    /**
     * makeValidator instantiates the validator used by the validation process, depending if the class is being used inside or
     * outside of Laravel.
     * @return \Illuminate\Validation\Validator
     */
    protected static function makeValidator($data, $rules, $customMessages, $attributeNames)
    {
        return static::getModelValidator()->make($data, $rules, $customMessages, $attributeNames);
    }

    /**
     * forceSave the model even if validation fails.
     * @return bool
     */
    public function forceSave($options = null)
    {
        return $this->saveInternal(['force' => true] + (array) $options);
    }

    /**
     * validate the model instance
     * @return bool
     */
    public function validate($rules = null, $customMessages = null, $attributeNames = null)
    {
        if ($this->validationErrors === null) {
            $this->validationErrors = new MessageBag;
        }

        $throwOnValidation = property_exists($this, 'throwOnValidation')
            ? $this->throwOnValidation
            : true;

        if (($this->fireModelEvent('validating') === false) || ($this->fireEvent('model.beforeValidate') === false)) {
            if ($throwOnValidation) {
                throw new ModelException($this);
            }

            return false;
        }

        if ($this->methodExists('beforeValidate')) {
            $this->beforeValidate();
        }

        // Perform validation
        $rules = is_null($rules) ? $this->rules : $rules;
        $rules = $this->processValidationRules($rules);
        $success = true;

        if (!empty($rules)) {
            $data = $this->getValidationAttributes();

            $lang = static::getModelValidator()->getTranslator();

            // Custom messages, translate internal references
            if (property_exists($this, 'customMessages') && is_null($customMessages)) {
                $customMessages = $this->customMessages;
            }

            if (is_null($customMessages)) {
                $customMessages = [];
            }

            $translatedCustomMessages = [];
            foreach ($customMessages as $rule => $customMessage) {
                $translatedCustomMessages[$rule] = $lang->get($customMessage);
            }

            $customMessages = $translatedCustomMessages;

            // Attribute names, translate internal references
            if (is_null($attributeNames)) {
                $attributeNames = [];
            }

            if (property_exists($this, 'attributeNames')) {
                $attributeNames = array_merge($this->attributeNames, $attributeNames);
            }

            $translatedAttributeNames = [];
            foreach ($attributeNames as $attribute => $attributeName) {
                $translatedAttributeNames[$attribute] = $lang->get($attributeName);
            }

            $attributeNames = $translatedAttributeNames;

            // Translate any externally defined attribute names
            $translations = $lang->get('validation.attributes');
            if (is_array($translations)) {
                $attributeNames = array_merge($translations, $attributeNames);
            }

            // Hand over to the validator
            $validator = static::makeValidator($data, $rules, $customMessages, $attributeNames);

            $success = $validator->passes();

            if ($success) {
                if ($this->validationErrors->count() > 0) {
                    $this->validationErrors = new MessageBag;
                }
            }
            else {
                $this->validationErrors = $validator->messages();

                // Flash input, if available
                if (
                    ($input = Input::getFacadeRoot()) &&
                    method_exists($input, 'hasSession') &&
                    $input->hasSession()
                ) {
                    $input->flash();
                }
            }
        }

        $this->fireModelEvent('validated', false);
        $this->fireEvent('model.afterValidate');

        if ($this->methodExists('afterValidate')) {
            $this->afterValidate();
        }

        if (!$success && $throwOnValidation) {
            throw new ModelException($this);
        }

        return $success;
    }

    /**
     * processValidationRules
     */
    protected function processValidationRules($rules)
    {
        // Run through field names and convert array notation field names to dot notation
        $rules = $this->processRuleFieldNames($rules);

        foreach ($rules as $field => $ruleParts) {
            // Trim empty rules
            if (is_string($ruleParts) && trim($ruleParts) === '') {
                unset($rules[$field]);
                continue;
            }

            // Normalize rulesets
            if (!is_array($ruleParts)) {
                $ruleParts = explode('|', $ruleParts);
            }

            // Analyse each rule individually
            foreach ($ruleParts as $key => $rulePart) {
                // Look for required:create and required:update rules
                if (starts_with($rulePart, 'required:create') && $this->exists) {
                    unset($ruleParts[$key]);
                }
                elseif (starts_with($rulePart, 'required:update') && !$this->exists) {
                    unset($ruleParts[$key]);
                }
            }

            $rules[$field] = $ruleParts;
        }

        return $rules;
    }

    /**
     * processRuleFieldNames converts any field names using array notation
     * (ie. `field[child]`) into dot notation (ie. `field.child`)
     * @param array $rules
     * @return array
     */
    protected function processRuleFieldNames($rules)
    {
        $processed = [];

        foreach ($rules as $field => $ruleParts) {
            $fieldName = $field;

            if (preg_match('/^.*?\[.*?\]/', $fieldName)) {
                $fieldName = str_replace('[]', '.*', $fieldName);
                $fieldName = str_replace(['[', ']'], ['.', ''], $fieldName);
            }

            $processed[$fieldName] = $ruleParts;
        }

        return $processed;
    }

    /**
     * isAttributeRequired determines if an attribute is required based on the validation rules.
     * @param  string  $attribute
     * @return bool
     */
    public function isAttributeRequired($attribute)
    {
        if (!isset($this->rules[$attribute])) {
            return false;
        }

        $ruleset = $this->rules[$attribute];

        if (is_array($ruleset)) {
            $ruleset = implode('|', $ruleset);
        }

        if (strpos($ruleset, 'required:create') !== false && $this->exists) {
            return false;
        }

        if (strpos($ruleset, 'required:update') !== false && !$this->exists) {
            return false;
        }

        if (strpos($ruleset, 'required_with') !== false) {
            $requiredWith = substr($ruleset, strpos($ruleset, 'required_with') + 14);
            $requiredWith = substr($requiredWith, 0, strpos($requiredWith, '|'));
            return $this->isAttributeRequired($requiredWith);
        }

        return strpos($ruleset, 'required') !== false;
    }

    /**
     * errors gets validation error message collection for the Model
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
    {
        return $this->validationErrors;
    }

    /**
     * validating creates a new native event for handling beforeValidate().
     * @param Closure|string $callback
     * @return void
     */
    public static function validating($callback)
    {
        static::registerModelEvent('validating', $callback);
    }

    /**
     * validated creates a new native event for handling afterValidate().
     * @param Closure|string $callback
     * @return void
     */
    public static function validated($callback)
    {
        static::registerModelEvent('validated', $callback);
    }

    /**
     * getModelValidator instance.
     * @return \Illuminate\Validation\Validator
     */
    public static function getModelValidator()
    {
        if (static::$validator === null) {
            static::$validator = Validator::getFacadeRoot();
        }

        return static::$validator;
    }

    /**
     * setModelValidator instance.
     * @param  \Illuminate\Validation\Validator
     * @return void
     */
    public static function setModelValidator($validator)
    {
        static::$validator = $validator;
    }

    /**
     * unsetModelValidator for models.
     * @return void
     */
    public static function unsetModelValidator()
    {
        static::$validator = null;
    }
}
