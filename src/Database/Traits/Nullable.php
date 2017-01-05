<?php namespace October\Rain\Database\Traits;

use Exception;

trait Nullable
{
    /**
     * @var array List of attribute names which should be set to null when empty.
     *
     * protected $nullable = [];
     */

    /**
     * Boot the nullable trait for a model
     *
     * @return void
     */
    public static function bootNullable()
    {
        if (!property_exists(get_called_class(), 'nullable')) {
            throw new Exception(sprintf(
                'You must define a $nullable property in %s to use the Nullable trait.', get_called_class()
            ));
        }

        static::extend(function($model) {
            $model->bindEvent('model.beforeSave', function() use ($model) {
                $model->nullableBeforeSave();
            });
        });
    }

    /**
     * Adds an attribute to the nullable attributes list
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function addNullable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->nullable = array_merge($this->nullable, $attributes);

        return $this;
    }

    /**
     * Nullify empty fields
     * @return void
     */
    public function nullableBeforeSave()
    {
        foreach ($this->nullable as $field) {
            if (is_string($this->{$field}) && empty($this->{$field})) {
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
