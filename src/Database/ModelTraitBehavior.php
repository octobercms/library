<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelBehavior;

/**
 * Base class for model trait behaviors.
 *
 * @package october\database
 * @author JoakimBo
 */
class ModelTraitBehavior extends ModelBehavior
{
    public function __construct($model)
    {
        parent::__construct($model);
        $this->bootTraits();
    }

    protected function bootTraits()
    {
        $class = static::class;
        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                $class::$method();
            }
        }
    }

    public function __set($name, $parameters)
    {
        if (!property_exists($this, $name))
        {
            $this->model->{$name} = $parameters;
        }
    }


    public function __get($name)
    {
        if (!property_exists($this, $name))
        {
            return $this->model->{$name};
        }
    }

    public function __call($name, $params)
    {
        if (!method_exists($this, $name) || !is_callable($this, $name))
        {
            return call_user_func_array([$this->model, $name], $params);
        }
    }
}
