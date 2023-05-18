<?php namespace October\Rain\Database\Concerns;

use Illuminate\Database\Eloquent\Factories\Factory;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  callable|array|int|null  $count
     * @param  callable|array  $state
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    public static function factory($count = null, $state = [])
    {
        $factory = static::newFactory() ?: static::factoryForModel(get_called_class());

        return $factory
            ->count(is_numeric($count) ? $count : null)
            ->state(is_callable($count) || is_array($count) ? $count : $state);
    }

    public function factoryForModel($class)
    {
        return static::$class.'Factory';
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        //
    }
}
