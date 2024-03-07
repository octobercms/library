<?php namespace October\Rain\Database\Traits;

use App;
use Lang;
use Input;
use Validator;
use October\Rain\Database\ModelException;
use Illuminate\Support\MessageBag;
use Exception;

/**
 * Validation trait for models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
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
     * @var bool validationForced is an internal marker to indicate if force option was used.
     */
    public $validationForced = false;

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
     * initializeValidation for this model
     */
    public function initializeValidation()
    {
        if (!is_array($this->rules)) {
            throw new Exception(sprintf(
                'The $rules property in %s must be an array to use the Validation trait.',
                static::class
            ));
        }

        $this->bindEvent('model.saveInternal', function() {
            $validationForced = $this->validationForced;

            if (($forceOption = $this->getSaveOption('force')) !== null) {
                $this->validationForced = $forceOption;
            }

            // If forcing the save event, the beforeValidate/afterValidate
            // events should still fire for consistency. So validate an
            // empty set of rules and messages.
            if ($this->validationForced) {
                $valid = $this->validate([], []);
            }
            else {
                $valid = $this->validate();
            }

            $this->validationForced = $validationForced;

            if (!$valid) {
                return false;
            }
        }, 500);
    }

    /**
     * setValidationAttributeNames programmatically sets multiple validation attribute names
     * @param array $attributeNames
     */
    public function setValidationAttributeNames($attributeNames)
    {
        $this->validationDefaultAttrNames = $attributeNames;
    }

    /**
     * setValidationAttributeName programmatically sets the validation attribute names, will take
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
     * addValidationRule will append a rule to the stack and reset the value as a processed array
     */
    public function addValidationRule(string $name, $definition)
    {
        $rules = $this->rules[$name] ?? [];
        if (!is_array($rules)) {
            $rules = explode('|', $rules);
        }

        $rules[] = $definition;

        $this->rules[$name] = $rules;
    }

    /**
     * removeValidationRule removes a validation rule from the stack and resets the value as a processed array
     */
    public function removeValidationRule(string $name, $definition)
    {
        $rules = $this->rules[$name] ?? [];
        if (!is_array($rules)) {
            $rules = explode('|', $rules);
        }

        foreach ($rules as $key => $rule) {
            if ($rule === $definition) {
                unset($rules[$key]);
            }
            elseif (
                is_string($definition) &&
                is_string($rule) &&
                str_starts_with($rule, "{$definition}:")
            ) {
                unset($rules[$key]);
            }
        }

        $this->rules[$name] = $rules;
    }

    /**
     * getRelationValidationValue handles attachments that validate differently to their simple values
     */
    protected function getRelationValidationValue($relationName)
    {
        // Locate records, with deferred logic
        if (
            $this->sessionKey &&
            !$this->relationLoaded($relationName) &&
            $this->hasDeferred($this->sessionKey, $relationName)
        ) {
            $data = $this->$relationName()->withDeferred($this->sessionKey)->get();
        }
        else {
            $data = $this->$relationName;
        }

        // DRY logic to post-process validation data
        $processValidationValue = function($value) {
            // Attachments
            if ($value instanceof \October\Rain\Database\Attach\File) {
                $localPath = $value->getLocalPath();

                // Exception handling for UploadedFile
                if (file_exists($localPath)) {
                    return new \Symfony\Component\HttpFoundation\File\UploadedFile(
                        $localPath,
                        $value->file_name,
                        $value->content_type,
                        null,
                        true
                    );
                }

                // Fallback to string
                $value = $localPath;
            }

            return $value;
        };

        // Process singular
        if ($this->isRelationTypeSingular($relationName)) {
            if ($data instanceof \Illuminate\Support\Collection) {
                $data = $data->last();
            }

            return $processValidationValue($data);
        }

        // Cast to primitive type
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->all();
        }

        if (!$data || !is_array($data)) {
            return null;
        }

        // Process multi
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $processValidationValue($value);
        }

        return $result;
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
        return $this->saveInternal((array) $options + ['force' => true, 'sessionKey' => $sessionKey]);
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

        // Perform validation
        $rules = is_null($rules) ? $this->rules : $rules;
        $rules = $this->processValidationRules($rules);
        $success = true;

        if (!empty($rules)) {
            $data = $this->getValidationAttributes();

            // Decode jsonable attribute values
            foreach ($this->getJsonable() as $jsonable) {
                $data[$jsonable] = $this->getAttribute($jsonable);
            }

            // Add relation values, if specified.
            foreach ($rules as $attribute => $rule) {
                if (!$this->hasRelation($attribute) || array_key_exists($attribute, $data)) {
                    continue;
                }

                $data[$attribute] = $this->getRelationValidationValue($attribute);
            }

            // Compatibility with Hashable trait
            // Remove all hashable values regardless, add the original values back
            // only if they are part of the data being validated.
            if (method_exists($this, 'getHashableAttributes')) {
                $cleanAttributes = array_diff_key($data, array_flip($this->getHashableAttributes()));
                $hashedAttributes = array_intersect_key($this->getOriginalHashValues(), $data);
                $data = array_merge($cleanAttributes, $hashedAttributes);
            }

            // Compatibility with Encryptable trait
            // Remove all encryptable values regardless, add the original values back
            // only if they are part of the data being validated.
            if (method_exists($this, 'getEncryptableAttributes')) {
                $cleanAttributes = array_diff_key($data, array_flip($this->getEncryptableAttributes()));
                $encryptedAttributes = array_intersect_key($this->getOriginalEncryptableValues(), $data);
                $data = array_merge($cleanAttributes, $encryptedAttributes);
            }

            // Custom messages, translate internal references
            if (property_exists($this, 'customMessages') && is_null($customMessages)) {
                $customMessages = $this->customMessages;
            }

            if (is_null($customMessages)) {
                $customMessages = [];
            }

            $transCustomMessages = [];
            foreach ($customMessages as $rule => $customMessage) {
                $transCustomMessages[$rule] = Lang::get($customMessage);
            }
            $customMessages = $transCustomMessages;

            // Attribute names, translate internal references
            $attrNames = (array) $this->validationDefaultAttrNames;

            if (property_exists($this, 'attributeNames')) {
                $attrNames = array_merge($attrNames, $this->attributeNames);
            }

            if ($attributeNames) {
                $attrNames = array_merge($attrNames, (array) $attributeNames);
            }

            $transAttrNames = [];
            foreach ($attrNames as $attribute => $attributeName) {
                $transAttrNames[$attribute] = Lang::get($attributeName);
            }
            $attrNames = $transAttrNames;

            // Translate any externally defined attribute names
            $translations = Lang::get('validation.attributes');
            if (is_array($translations)) {
                $attrNames = array_merge($translations, $attrNames);
            }

            // Hand over to the validator
            $validator = self::makeValidator(
                $data,
                $rules,
                $customMessages,
                $attrNames,
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
        // Run through field names and convert array notation field names to dot notation
        $rules = $this->processRuleFieldNames($rules);

        foreach ($rules as $field => $ruleParts) {
            // Trim empty rules
            if (is_string($ruleParts) && trim($ruleParts) === '') {
                unset($rules[$field]);
                continue;
            }

            // Normalize rule sets
            if (!is_array($ruleParts)) {
                $ruleParts = explode('|', $ruleParts);
            }

            // Analyze each rule individually
            foreach ($ruleParts as $key => $rulePart) {
                // Allow rule objects
                if (is_object($rulePart)) {
                    continue;
                }
                // Remove primary key unique validation rule if the model already exists
                if (str_starts_with($rulePart, 'unique')) {
                    $ruleParts[$key] = $this->processValidationUniqueRule($rulePart, $field);
                }
                // Look for required:create and required:update rules
                elseif (str_starts_with($rulePart, 'required:create') && $this->exists) {
                    unset($ruleParts[$key]);
                }
                elseif (str_starts_with($rulePart, 'required:update') && !$this->exists) {
                    unset($ruleParts[$key]);
                }
            }

            $rules[$field] = $ruleParts;
        }

        return $rules;
    }

    /**
     * processRuleFieldNames processes field names in a rule array
     * Converts any field names using array notation (ie. `field[child]`) into dot notation (ie. `field.child`)
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
     * processValidationUniqueRule rebuilds the unique validation rule to force for the existing key
     * exclusion for existing models. It also checks for unique rules without a table name and includes
     * the table name, since this is required by Laravel but not October.
     * @param string $definition
     * @param string $fieldName
     * @return string
     */
    protected function processValidationUniqueRule($definition, $fieldName)
    {
        if (!$this->exists) {
            if ($definition === 'unique') {
                return $definition . ':' . $this->getTable();
            }
            return $definition;
        }

        [$ruleName, $ruleDefinition] = array_pad(explode(':', $definition, 2), 2, '');
        [$tableName, $column, $key, $keyName, $whereColumn, $whereValue] = array_pad(explode(',', $ruleDefinition, 6), 6, null);

        $tableName = $tableName ?: $this->getTable();
        $column = $column ?: $fieldName;
        $key = $keyName ? $this->$keyName : $this->getKey();
        $keyName = $keyName ?: $this->getKeyName();

        $params = [$tableName, $column, $key, $keyName];

        if ($whereColumn) {
            $params[] = $whereColumn;
        }

        if ($whereValue) {
            $params[] = $whereValue;
        }

        return $ruleName . ':' . implode(',', $params);
    }

    /**
     * isAttributeRequired determines if an attribute is required based on the validation rules.
     * checkDependencies checks the attribute dependencies (for required_if & required_with rules).
     * Note that it will only be checked up to the next level, if another dependent rule is found
     * then it will just assume the field is required.
     * @param  string  $attribute
     * @param bool $checkDependencies
     * @return bool
     */
    public function isAttributeRequired($attribute, $checkDependencies = true)
    {
        if (!isset($this->rules[$attribute])) {
            return false;
        }

        $ruleSet = $this->rules[$attribute];

        if (is_array($ruleSet)) {
            $ruleSet = implode('|', $ruleSet);
        }

        if (strpos($ruleSet, 'required:create') !== false && $this->exists) {
            return false;
        }

        if (strpos($ruleSet, 'required:update') !== false && !$this->exists) {
            return false;
        }

        if (strpos($ruleSet, 'required_with') !== false) {
            if (!$checkDependencies) {
                return true;
            }

            $requiredWith = substr($ruleSet, strpos($ruleSet, 'required_with') + 14);

            if (strpos($requiredWith, '|') !== false) {
                $requiredWith = substr($requiredWith, 0, strpos($requiredWith, '|'));
            }

            return $this->isAttributeRequired($requiredWith, false);
        }

        if (strpos($ruleSet, 'required_if') !== false) {
            if (!$checkDependencies) {
                return true;
            }

            $requiredIf = substr($ruleSet, strpos($ruleSet, 'required_if') + 12);
            $requiredIf = substr($requiredIf, 0, strpos($requiredIf, ','));

            return $this->isAttributeRequired($requiredIf, false);
        }

        return strpos($ruleSet, 'required') !== false;
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
