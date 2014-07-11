<?php namespace October\Rain\Database\Traits;

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
    private $originalHashableValues = [];

    /**
     * Boot the hashable trait for a model.
     *
     * @return void
     */
    public static function bootHashable()
    {
        if (!property_exists(get_called_class(), 'hashable'))
            throw new Exception(sprintf('You must define a $hashable property in %s to use the Hashable trait.', get_called_class()));

        static::extend(function($model){
            $model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model) {

                /*
                 * Hash required fields when necessary
                 */
                $hashable = $model->getHashableAttributes();
                if (in_array($key, $hashable) && !empty($value))
                    return $model->makeHashValue($value);

            });
        });
    }

    public function makeHashValue($key, $value)
    {
        $this->originalHashableValues[$key] = $value;
        return Hash::make($value);
    }

    /**
     * Returns a collection of fields that will be hashed.
     */
    public function getHashableAttributes()
    {
        return $this->hashable;
    }

    /**
     * Returns the original values of any hashed attributes.
     */
    public function getOriginalHashValues()
    {
        return $this->originalHashableValues;
    }

    /**
     * Returns the original values of any hashed attributes.
     */
    public function getOriginalHashValue($attribute)
    {
        return isset($this->originalHashableValues[$attribute])
            ? $this->originalHashableValues[$attribute]
            : null;
    }
}