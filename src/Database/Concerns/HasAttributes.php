<?php namespace October\Rain\Database\Concerns;

use October\Rain\Support\Str;
use Exception;

/**
 * HasAttributes concern for a model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasAttributes
{
    /**
     * attributesToArray converts the model's attributes to an array.
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();

        /*
         * Before Event
         */
        foreach ($attributes as $key => $value) {
            if (($eventValue = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null) {
                $attributes[$key] = $eventValue;
            }
        }

        /*
         * Dates
         */
        foreach ($this->getDates() as $key) {
            if (!isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        /*
         * Mutate
         */
        $mutatedAttributes = $this->getMutatedAttributes();

        foreach ($mutatedAttributes as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }

            $attributes[$key] = $this->mutateAttributeForArray(
                $key,
                $attributes[$key]
            );
        }

        /*
         * Casts
         */
        foreach ($this->casts as $key => $value) {
            if (
                !array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)
            ) {
                continue;
            }

            $attributes[$key] = $this->castAttribute(
                $key,
                $attributes[$key]
            );
        }

        /*
         * Appends
         */
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        /*
         * Jsonable
         */
        foreach ($this->jsonable as $key) {
            if (
                !array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)
            ) {
                continue;
            }

            // Prevent double decoding of jsonable attributes.
            if (!is_string($attributes[$key])) {
                continue;
            }

            $jsonValue = json_decode($attributes[$key], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attributes[$key] = $jsonValue;
            }
        }

        /*
         * After Event
         */
        foreach ($attributes as $key => $value) {
            if (($eventValue = $this->fireEvent('model.getAttribute', [$key, $value], true)) !== null) {
                $attributes[$key] = $eventValue;
            }
        }

        return $attributes;
    }

    /**
     * getAttribute from the model.
     * Overrided from {@link Eloquent} to implement recognition of the relation.
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if ($this->hasRelation($key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * getAttributeValue gets a plain attribute (not a relationship).
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        /**
         * @event model.beforeGetAttribute
         * Called before the model attribute is retrieved
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeGetAttribute', function ((string) $key) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-look-at') {
         *             return 'you are not allowed here';
         *         }
         *     });
         *
         */
        if (($attr = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null) {
            return $attr;
        }

        $attr = parent::getAttributeValue($key);

        /*
         * Return valid json (boolean, array) if valid, otherwise
         * jsonable fields will return a string for invalid data.
         */
        if ($this->isJsonable($key) && !empty($attr)) {
            $_attr = json_decode($attr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attr = $_attr;
            }
        }

        /**
         * @event model.getAttribute
         * Called after the model attribute is retrieved
         *
         * Example usage:
         *
         *     $model->bindEvent('model.getAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-look-at') {
         *             return "Totally not $value";
         *         }
         *     });
         *
         */
        if (($_attr = $this->fireEvent('model.getAttribute', [$key, $attr], true)) !== null) {
            return $_attr;
        }

        return $attr;
    }

    /**
     * hasGetMutator determines if a get mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return $this->methodExists('get'.Str::studly($key).'Attribute');
    }

    /**
     * setAttribute sets a given attribute on the model.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        /*
         * Attempting to set attribute [null] on model.
         */
        if (empty($key)) {
            throw new Exception('Cannot access empty model attribute.');
        }

        /*
         * Handle direct relation setting
         */
        if ($this->hasRelation($key) && !$this->hasSetMutator($key)) {
            return $this->setRelationValue($key, $value);
        }

        /**
         * @event model.beforeSetAttribute
         * Called before the model attribute is set
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeSetAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'do-not-touch') {
         *             return "$value has been touched";
         *         }
         *     });
         *
         */
        if (($_value = $this->fireEvent('model.beforeSetAttribute', [$key, $value], true)) !== null) {
            $value = $_value;
        }

        /*
         * Jsonable
         */
        if ($this->isJsonable($key) && (!empty($value) || is_array($value))) {
            $value = json_encode($value);
        }

        /*
         * Trim strings
         */
        if ($this->trimStrings && is_string($value)) {
            $value = trim($value);
        }

        $result = parent::setAttribute($key, $value);

        /**
         * @event model.setAttribute
         * Called after the model attribute is set
         *
         * Example usage:
         *
         *     $model->bindEvent('model.setAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'do-not-touch') {
         *             \Log::info("{$key} has been touched and set to {$value}!")
         *         }
         *     });
         *
         */
        $this->fireEvent('model.setAttribute', [$key, $value]);

        return $result;
    }

    /**
     * hasSetMutator determines if a set mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return $this->methodExists('set'.Str::studly($key).'Attribute');
    }

    /**
     * addCasts adds attribute casts for the model.
     *
     * @param  array $attributes
     * @return void
     */
    public function addCasts($attributes)
    {
        $this->casts = array_merge($this->casts, $attributes);
    }

    /**
     * addDateAttribute adds a datetime attribute to convert to an instance
     * of Carbon/DateTime object.
     * @param string   $attribute
     * @return void
     */
    public function addDateAttribute($attribute)
    {
        if (in_array($attribute, $this->dates)) {
            return;
        }

        $this->dates[] = $attribute;
    }

    /**
     * addFillable attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addFillable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->fillable = array_merge($this->fillable, $attributes);
    }
}
