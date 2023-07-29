<?php namespace October\Rain\Database\Factories;

/**
 * HasFactory implements factory support for a model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges, Samuell
 */
trait HasFactory
{
    /**
     * factory gets a new factory instance for the model.
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

    /**
     * factoryForModel guesses a factory class based on the model class
     */
    protected static function factoryForModel(string $modelName)
    {
        if (strpos($modelName, 'App\\') === 0) {
            $factory = str_replace('Models\\', 'Database\\Factories\\', $modelName) . 'Factory';
        }
        else {
            $factory = str_replace('Models\\', 'Updates\\Factories\\', $modelName) . 'Factory';
        }

        return $factory::new();
    }

    /**
     * newFactory creates a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        //
    }
}
