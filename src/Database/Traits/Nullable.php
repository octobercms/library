<?php namespace October\Rain\Database\Traits;

use Exception;

/**
 * Nullable will set empty attributes to values equivalent to NULL in the database.
 */
trait Nullable
{
    /**
     * @var array nullable attribute names which should be set to null when empty.
     *
     * protected $nullable = [];
     */

    /**
     * bootNullable trait for a model
     */
    public static function bootNullable()
    {
        if (!property_exists(get_called_class(), 'nullable')) {
            throw new Exception(sprintf(
                'You must define a $nullable property in %s to use the Nullable trait.',
                get_called_class()
            ));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.beforeSave', function () use ($model) {
                $model->nullableBeforeSave();
            });
        });
    }

    /**
     * addNullable attribute to the nullable attributes list
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addNullable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->nullable = array_merge($this->nullable, $attributes);

        // @deprecated
        return $this;
    }

    /**
     * checkNullableValue checks if the supplied value is empty, excluding zero.
     * @param  string $value Value to check
     * @return bool
     */
    public function checkNullableValue($value)
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
