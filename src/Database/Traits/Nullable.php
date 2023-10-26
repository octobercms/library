<?php namespace October\Rain\Database\Traits;

use Exception;

/**
 * Nullable will set empty attributes to values equivalent to NULL in the database.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Nullable
{
    /**
     * @var array nullable attribute names which should be set to null when empty.
     *
     * protected $nullable = [];
     */

    /**
     * initializeNullable trait for a model
     */
    public function initializeNullable()
    {
        if (!is_array($this->nullable)) {
            throw new Exception(sprintf(
                'The $nullable property in %s must be an array to use the Nullable trait.',
                static::class
            ));
        }

        $this->bindEvent('model.beforeSaveDone', [$this, 'nullableBeforeSave']);
    }

    /**
     * addNullable attribute to the nullable attributes list
     */
    public function addNullable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->nullable = array_merge($this->nullable, $attributes);
    }

    /**
     * checkNullableValue checks if the supplied value is empty, excluding zero.
     */
    public function checkNullableValue($value): bool
    {
        if ($value === 0 || $value === '0' || $value === 0.0 || $value === false) {
            return false;
        }

        return empty($value);
    }

    /**
     * nullableBeforeSave will nullify empty fields at time of saving.
     */
    public function nullableBeforeSave()
    {
        foreach ($this->nullable as $field) {
            if ($this->checkNullableValue($this->{$field})) {
                if ($this->exists) {
                    $this->attributes[$field] = null;
                }
                else {
                    unset($this->attributes[$field]);
                }
            }
        }
    }
}
