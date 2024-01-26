<?php namespace October\Rain\Database\Traits;

use Hash;
use Exception;

/**
 * Hashable
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
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
     * initializeHashable trait for a model.
     */
    public function initializeHashable()
    {
        if (!is_array($this->hashable)) {
            throw new Exception(sprintf(
                'The $hashable property in %s must be an array to use the Hashable trait.',
                static::class
            ));
        }

        // Hash required fields when necessary
        $this->bindEvent('model.beforeSetAttribute', function ($key, $value) {
            $hashable = $this->getHashableAttributes();
            if (in_array($key, $hashable) && !empty($value)) {
                return $this->makeHashValue($key, $value);
            }
        });
    }

    /**
     * addHashable adds an attribute to the hashable attributes list
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
     * makeHashValue hashes an attribute value and saves it in the original locker.
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
     * checkHashValue checks if the supplied plain value matches the stored hash value.
     * @param  string $key   Attribute to check
     * @param  string $value Value to check
     * @return bool
     */
    public function checkHashValue($key, $value)
    {
        return Hash::check($value, $this->{$key});
    }

    /**
     * getHashableAttributes returns a collection of fields that will be hashed.
     * @return array
     */
    public function getHashableAttributes()
    {
        return $this->hashable;
    }

    /**
     * getOriginalHashValues returns the original values of any hashed attributes.
     * @return array
     */
    public function getOriginalHashValues()
    {
        return $this->originalHashableValues;
    }

    /**
     * getOriginalHashValue returns the original values of any hashed attributes.
     * @return mixed
     */
    public function getOriginalHashValue($attribute)
    {
        return $this->originalHashableValues[$attribute] ?? null;
    }
}
