<?php namespace October\Rain\Database\Traits;

use Exception;

trait Nullable 
{

    /**
     * Boot the nullable trait for a model
     *
     * @return void
     */
    public static function bootNullable()
    {
        if (!property_exists(get_called_class(), 'nullable')) {
            throw new Exception(sprintf('You must define a $nullable property in %s to use the Nullable trait.', get_called_class()));
        }

        static::extend(function($model) {
            $model->bindEvent('model.beforeSave', function() use ($model) {
                $model->nullableBeforeSave();
            });
        });
    }

    /**
     * Nullify empty fields
     *
     * @return void
     */
    public function nullableBeforeSave()
    {
        foreach ($this->nullable as $field) {
            if (empty($this->$field)) {
                $this->$field = null;
            }
        }
    }
}
