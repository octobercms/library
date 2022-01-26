<?php namespace October\Rain\Database\Concerns;

/**
 * HasJsonable concern for a model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasJsonable
{
    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * addJsonable attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addJsonable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->jsonable = array_merge($this->jsonable, $attributes);
    }

    /**
     * isJsonable checks if an attribute is jsonable or not.
     *
     * @return array
     */
    public function isJsonable($key)
    {
        return in_array($key, $this->jsonable);
    }

    /**
     * getJsonable attributes name
     *
     * @return array
     */
    public function getJsonable()
    {
        return $this->jsonable;
    }

    /**
     * jsonable attributes set for the model.
     *
     * @param  array  $jsonable
     * @return $this
     */
    public function jsonable(array $jsonable)
    {
        $this->jsonable = $jsonable;

        return $this;
    }
}
