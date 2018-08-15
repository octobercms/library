<?php namespace October\Rain\Database\Traits;

use Hash;
use Exception;

trait Hashable
{
    /**
     * @var array List of attribute names which should be hashed using the Bcrypt hashing algorithm.
     *
     * protected $hashable = [];
     */

    /**
     * @var array List of original attribute values before they were hashed.
     */
    protected $originalHashableValues = [];

    /**
     * Boot the hashable trait for a model.
     * @return void
     */
    public static function bootHashable()
    {
        if (!property_exists(get_called_class(), 'hashable')) {
            throw new Exception(sprintf(
                'You must define a $hashable property in %s to use the Hashable trait.', get_called_class()
            ));
        }
        /*
         * Hash required fields when necessary
         */
        static::extend(function($model){
            $model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model) {
                $hashable = $model->getHashableAttributes();
                if (in_array($key, $hashable) && !empty($value)) {
                    return $model->makeHashValue($key, $value);
                }
            });
        });
    }

    /**
     * Adds an attribute to the hashable attributes list
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function addHashable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->hashable = array_merge($this->hashable, $attributes);

        return $this;
    }

    /**
     * Hashes an attribute value and saves it in the original locker.
     * @param  string $key   Attribute
     * @param  string $value Value to hash
     * @return string        Hashed value
     */
    public function makeHashValue($key, $value)
    {
        $this->originalHashableValues[$key] = $value;
        return Hash::make($value);
    }

    /**
     * Checks if the supplied plain value matches the stored hash value.
     * @param  string $key   Attribute to check
     * @param  string $value Value to check
     * @return bool
     */
    public function checkHashValue($key, $value)
    {
        return Hash::check($value, $this->{$key});
    }

    /**
     * Returns a collection of fields that will be hashed.
     * @return array
     */
    public function getHashableAttributes()
    {
        return $this->hashable;
    }

    /**
     * Returns the original values of any hashed attributes.
     * @return array
     */
    public function getOriginalHashValues()
    {
        return $this->originalHashableValues;
    }

    /**
     * Returns the original values of any hashed attributes.
     * @return mixed
     */
    public function getOriginalHashValue($attribute)
    {
        return $this->originalHashableValues[$attribute] ?? null;
    }

    /**
     * @deprecated use self::addHashable()
     * Remove this method if year >= 2018
     */
    public function addHashableAttribute($attribute)
    {
        traceLog('The addHashableAttribute() method is deprecated, use addHashable() instead.');

        return $this->addHashable($attribute);
    }
}
