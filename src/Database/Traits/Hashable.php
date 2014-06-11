<?php namespace October\Rain\Database\Traits;

trait Hashable
{
    /**
     * @var array List of attribute names which should be hashed using the Bcrypt hashing algorithm.
     */
    protected $hashable = [];

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