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

        // Dates
        $attributes = $this->addDateAttributesToArray($attributes);

        // Mutate
        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Casts
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        // Appends
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        // Jsonable
        $attributes = $this->addJsonableAttributesToArray(
            $attributes, $mutatedAttributes
        );

        return $attributes;
    }

    /**
     * getAttribute from the model.
     * Overridden from {@link Eloquent} to implement recognition of the relation.
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (
            array_key_exists($key, $this->attributes) ||
            $this->hasGetMutator($key) ||
            $this->hasAttributeMutator($key) ||
            $this->isClassCastable($key)
        ) {
            return $this->getAttributeValue($key);
        }

        return $this->getRelationValue($key);
    }

    /**
     * getRelationValue gets a relationship value from a method.
     * Overridden from {@link Eloquent} to implement recognition of the relation
     * using October Rain's property-based relation definitions.
     * @param string $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        // Check both October and Laravel
        if (!$this->hasRelation($key) && !$this->isRelation($key)) {
            return;
        }

        if ($this->attemptToAutoloadRelation($key)) {
            return $this->relations[$key];
        }

        if ($this->preventsLazyLoading) {
            $this->handleLazyLoadingViolation($key);
        }

        return $this->getRelationshipFromMethod($key);
    }

    /**
     * getAttributeValue gets a plain attribute (not a relationship).
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $attr = parent::getAttributeValue($key);

        // Return valid json (boolean, array) if valid, otherwise
        // jsonable fields will return a string for invalid data.
        if ($this->isJsonable($key) && !empty($attr)) {
            $_attr = json_decode($attr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attr = $_attr;
            }
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
        // Attempting to set attribute [null] on model.
        if (empty($key)) {
            throw new Exception('Cannot access empty model attribute.');
        }

        // Handle direct relation setting
        if ($this->hasRelation($key) && !$this->hasSetMutator($key)) {
            return $this->setRelationSimpleValue($key, $value);
        }

        // Jsonable
        if ($this->isJsonable($key) && (!empty($value) || is_array($value))) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        // Trim strings
        if ($this->trimStrings && is_string($value)) {
            $value = trim($value);
        }

        return parent::setAttribute($key, $value);
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
     * getDates returns the attributes that should be converted to dates.
     * @return array
     */
    public function getDates()
    {
        if (!$this->usesTimestamps()) {
            return $this->dates;
        }

        $defaults = [
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        return array_unique(array_merge($this->dates, $defaults));
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
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addFillable($attributes = null)
    {
        $this->fillable = array_merge(
            $this->fillable, is_array($attributes) ? $attributes : func_get_args()
        );
    }

    /**
     * addVisible attributes for the model.
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addVisible($attributes = null)
    {
        $this->visible = array_merge(
            $this->visible, is_array($attributes) ? $attributes : func_get_args()
        );
    }
}
