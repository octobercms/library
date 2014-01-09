<?php namespace October\Rain\Support;

/**
 * Hot Facades
 *
 * Allows "hot-swappable" facades, since `class_alias` cannot be changed dynamically. 
 * Facades loaded here can be changed on the fly.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class FacadeLoader
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * The array of class facades.
     * @var array
     */
    protected $facades;

    /**
     * Add an facade to the loader.
     * @param  string  $class
     * @param  string  $facade
     * @return void
     */
    public function facade($class, $facade)
    {
        $this->facades[$class] = $facade;

        if (method_exists($facade, 'registerSingletonInstance'))
            $facade::registerSingletonInstance();

        if (!class_exists($class)) {
            eval(sprintf('class %s extends %s {}', $class, '\October\Rain\Support\FacadeBase'));
        }
    }

    /**
     * Returns an array of registered and active facades.
     */
    public function getFacades()
    {
        return $this->facades;
    }
}