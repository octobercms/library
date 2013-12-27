<?php namespace October\Rain\Foundation;

class FacadeLoader
{
    use \October\Rain\Support\Singleton;

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
            eval(sprintf('class %s extends %s {}', $class, '\October\Rain\Foundation\FacadeBase'));
        }
    }

    public function getFacades()
    {
        return $this->facades;
    }
}