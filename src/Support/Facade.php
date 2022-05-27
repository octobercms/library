<?php namespace October\Rain\Support;

use Illuminate\Support\Facades\Facade as FacadeParent;

/**
 * Facade base class that adds the ability to define a fallback instance
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Facade extends FacadeParent
{
    /**
     * @inheritDoc
     */
    protected static function resolveFacadeInstance($name)
    {
        if (
            !is_object($name) &&
            !is_null(static::$app) &&
            !static::$app->bound($name) &&
            ($instance = static::getFacadeInstance()) !== null
        ) {
            static::$app->instance($name, $instance);
        }

        return parent::resolveFacadeInstance($name);
    }

    /**
     * getFacadeInstance if the accessor is not found via getFacadeAccessor,
     * use this instance as a fallback.
     * @return mixed
     */
    protected static function getFacadeInstance()
    {
        return null;
    }

    /**
     * defaultAliases gets the application default aliases.
     * @return \Illuminate\Support\Collection
     */
    public static function defaultAliases()
    {
        return parent::defaultAliases()->merge([
            'Model' => \October\Rain\Database\Model::class,
            'Event' => \October\Rain\Support\Facades\Event::class,
            'Mail' => \October\Rain\Support\Facades\Mail::class,
            'File' => \October\Rain\Support\Facades\File::class,
            'Config' => \October\Rain\Support\Facades\Config::class,
            'Seeder' => \October\Rain\Database\Updates\Seeder::class,
            'Input' => \October\Rain\Support\Facades\Input::class,
            'Str' => \October\Rain\Support\Facades\Str::class,
        ]);
    }
}
