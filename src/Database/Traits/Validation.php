<?php namespace October\Rain\Database\Traits;

use App;
use Lang;
use Input;
use October\Rain\Database\ModelException;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator;
use Exception;

trait Validation
{
    /**
     * @var array rules for validation
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
     * @var \Illuminate\Support\MessageBag validationErrors message bag instance containing
     * validation error messages
     */
    protected $validationErrors;

    /**
     * @var array validationDefaultAttrNames default custom attribute names
     */
    protected $validationDefaultAttrNames = [];

    /**
     * bootValidation for this model
     */
    public static function bootValidation()
    {
        if (!property_exists(get_called_class(), 'rules')) {
            throw new Exception(sprintf(
                'You must define a $rules property in %s to use the Validation trait.',
                get_called_class()
            ));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function ($data, $options) use ($model) {
                /*
                 * If forcing the save event, the beforeValidate/afterValidate
                 * events should still fire for consistency. So validate an
                 * empty set of rules and messages.
                 */
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
     * setValidationAttributeNames programatically sets multiple validation attribute names
     * @param array $attributeNames
     */
    public function setValidationAttributeNames($attributeNames)
    {
        $this->validationDefaultAttrNames = $attributeNames;
    }

    /**
     * setValidationAttributeName programatically sets the validation attribute names, will take
     * lower priority to model defined attribute names found in `$attributeNames`
     * @param string $attr
     * @param string $name
     * @return void
     */
    public function setValidationAttributeName($attr, $name)
    {
        $this->validationDefaultAttrNames[$attr] = $name;
    }

    /**
     * getValidationAttributes returns the model data used for validation
     * @return array
     */
    protected function getValidationAttributes()
    {
        return $this->getAttributes();
    }

    /**
     * getRelationValidationValue handles attachments that validate differently to their simple values
     */
    protected function getRelationValidationValue($relationName)
    {
        $relationType = $this->getRelationType($relationName);

        if ($relationType === 'attachOne' || $relationType === 'attachMany') {
            return $this->$relationName()->getValidationValue();
        }

        return $this->getRelationValue($relationName);
    }

    /**
     * makeValidator instantiates the validator used by the validation process, depending if the
     * class is being used inside or outside of Laravel. Optional connection string to make
     * the validator use a different database connection than the default connection.
     * @return \Illuminate\Validation\Validator
     */
    protected static function makeValidator($data, $rules, $customMessages, $attributeNames, $connection = null)
    {
        $validator = Validator::make($data, $rules, $customMessages, $attributeNames);

        if ($connection !== null) {
            $verifier = App::make('validation.presence');
            $verifier->setConnection($connection);
            $validator->setPresenceVerifier($verifier);
        }

        return $validator;
    }

    /**
     * forceSave the model even if validation fails
     * @return bool
     */
    public function forceSave($options = null, $sessionKey = null)
    {
        $this->sessionKey = $sessionKey;
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

        /**
         * @event model.beforeValidate
         * Called before the model is validated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeValidate', function () use (\October\Rain\Database\Model $model) {
         *         // Prevent anything from validating ever!
         *         return false;
         *     });
         *
         */
        if (($this->fireModelEvent('validating') === false) || ($this->fireEvent('model.beforeValidate', [], true) === false)) {
            if ($throwOnValidation) {
                throw new ModelException($this);
            }

            return false;
        }

        if ($this->methodExists('beforeValidate')) {
            $this->beforeValidate();
        }

        /*
         * Perform validation
         */
        $rules = is_null($rules) ? $this->rules : $rules;
        $rules = $this->processValidationRules($rules);
        $success = true;

        if (!empty($rules)) {
            $data = $this->getValidationAttributes();

            /*
             * Decode jsonable attribute values
             */
            foreach ($this->getJsonable() as $jsonable) {
                $data[$jsonable] = $this->getAttribute($jsonable);
            }

            /*
             * Add relation values, if specified.
             */
            foreach ($rules as $attribute => $rule) {
                if (
                    !$this->hasRelation($attribute) ||
                    array_key_exists($attribute, $data)
                ) {
                    continue;
                }

                $data[$attribute] = $this->getRelationValidationValue($attribute);
            }

            /*
             * Compatibility with Hashable trait
             * Remove all hashable values regardless, add the original values back
             * only if they are part of the data being validated.
             */
            if (method_exists($this, 'getHashableAttributes')) {
                $cleanAttributes = array_diff_key($data, array_flip($this->getHashableAttributes()));
                $hashedAttributes = array_intersect_key($this->getOriginalHashValues(), $data);
                $data = array_merge($cleanAttributes, $hashedAttributes);
            }

            /*
             * Compatibility with Encryptable trait
             * Remove all encryptable values regardless, add the original values back
             * only if they are part of the data being validated.
             */
            if (method_exists($this, 'getEncryptableAttributes')) {
                $cleanAttributes = array_diff_key($data, array_flip($this->getEncryptableAttributes()));
                $encryptedAttributes = array_intersect_key($this->getOriginalEncryptableValues(), $data);
                $data = array_merge($cleanAttributes, $encryptedAttributes);
            }

            /*
             * Custom messages, translate internal references
             */
            if (property_exists($this, 'customMessages') && is_null($customMessages)) {
                $customMessages = $this->customMessages;
            }

            if (is_null($customMessages)) {
                $customMessages = [];
            }

            $translatedCustomMessages = [];
            foreach ($customMessages as $rule => $customMessage) {
                $translatedCustomMessages[$rule] = Lang::get($customMessage);
            }

            $customMessages = $translatedCustomMessages;

            /*
             * Attribute names, translate internal references
             */
            if (is_null($attributeNames)) {
                $attributeNames = [];
            }

            $attributeNames = array_merge($this->validationDefaultAttrNames, $attributeNames);

            if (property_exists($this, 'attributeNames')) {
                $attributeNames = array_merge($this->attributeNames, $attributeNames);
            }

            $translatedAttributeNames = [];
            foreach ($attributeNames as $attribute => $attributeName) {
                $translatedAttributeNames[$attribute] = Lang::get($attributeName);
            }

            $attributeNames = $translatedAttributeNames;

            /*
             * Translate any externally defined attribute names
             */
            $translations = Lang::get('validation.attributes');
            if (is_array($translations)) {
                $attributeNames = array_merge($translations, $attributeNames);
            }

            /*
             * Hand over to the validator
             */
            $validator = self::makeValidator(
                $data,
                $rules,
                $customMessages,
                $attributeNames,
                $this->getConnectionName()
            );

            $success = $validator->passes();

            if ($success) {
                if ($this->validationErrors->count() > 0) {
                    $this->validationErrors = new MessageBag;
                }
            }
            else {
                $this->validationErrors = $validator->messages();
                if (Input::hasSession()) {
                    Input::flash();
                }
            }
        }

        /**
         * @event model.afterValidate
         * Called after the model is validated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterValidate', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info("{$model->name} successfully passed validation");
         *     });
         *
         */
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
        /*
         * Run through field names and convert array notation field names to dot notation
         */
        $rules = $this->processRuleFieldNames($rules);

        foreach ($rules as $field => $ruleParts) {
            /*
             * Trim empty rules
             */
            if (is_string($ruleParts) && trim($ruleParts) === '') {
                unset($rules[$field]);
                continue;
            }

            /*
             * Normalize rulesets
             */
            if (!is_array($ruleParts)) {
                $ruleParts = explode('|', $ruleParts);
            }

            /*
             * Analyse each rule individually
             */
            foreach ($ruleParts as $key => $rulePart) {
                /*
                 * Remove primary key unique validation rule if the model already exists
                 */
                if (starts_with($rulePart, 'unique') && $this->exists) {
                    $ruleParts[$key] = $this->processValidationUniqueRule($rulePart, $field);
                }
                /*
                 * Look for required:create and required:update rules
                 */
                elseif (starts_with($rulePart, 'required:create') && $this->exists) {
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
     * processRuleFieldNames processes field names in a rule array
     *
     * Converts any field names using array notation (ie. `field[child]`) into dot notation (ie. `field.child`)
     *
     * @param array $rules Rules array
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
     * processValidationUniqueRule rebuilds the unique validation rule to force for the existing ID
     * @param string $definition
     * @param string $fieldName
     * @return string
     */
    protected function processValidationUniqueRule($definition, $fieldName)
    {
        list(
            $table,
            $column,
            $key,
            $keyName,
            $whereColumn,
            $whereValue
        ) = array_pad(explode(',', $definition, 6), 6, null);

        $table = 'unique:' . $this->getTable();
        $column = $column ?: $fieldName;
        $key = $keyName ? $this->$keyName : $this->getKey();
        $keyName = $keyName ?: $this->getKeyName();

        $params = [$table, $column, $key, $keyName];

        if ($whereColumn) {
            $params[] = $whereColumn;
        }

        if ($whereValue) {
            $params[] = $whereValue;
        }

        return implode(',', $params);
    }

    /**
     * isAttributeRequired determines if an attribute is required based on the validation rules.
     * checkDependencies checks the attribute dependencies (for required_if & required_with rules).
     * Note that it will only be checked up to the next level, if another dependent rule is found
     * then it will just assume the field is required.
     * @param  string  $attribute
     * @param boolean $checkDependencies
     * @return boolean
     */
    public function isAttributeRequired($attribute, $checkDependencies = true)
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
            if (!$checkDependencies) {
                return true;
            }

            $requiredWith = substr($ruleset, strpos($ruleset, 'required_with') + 14);

            if (strpos($requiredWith, '|') !== false) {
                $requiredWith = substr($requiredWith, 0, strpos($requiredWith, '|'));
            }

            return $this->isAttributeRequired($requiredWith, false);
        }

        if (strpos($ruleset, 'required_if') !== false) {
            if (!$checkDependencies) {
                return true;
            }

            $requiredIf = substr($ruleset, strpos($ruleset, 'required_if') + 12);
            $requiredIf = substr($requiredIf, 0, strpos($requiredIf, ','));

            return $this->isAttributeRequired($requiredIf, false);
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
     * validating creates a new native event for handling beforeValidate()
     * @param Closure|string $callback
     * @return void
     */
    public static function validating($callback)
    {
        static::registerModelEvent('validating', $callback);
    }

    /**
     * validated create a new native event for handling afterValidate()
     * @param Closure|string $callback
     * @return void
     */
    public static function validated($callback)
    {
        static::registerModelEvent('validated', $callback);
    }
}
