<?php namespace October\Rain\Support;

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

    public function getFacades()
    {
        return $this->facades;
    }
}