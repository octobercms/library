<?php namespace October\Rain\Foundation;

class AliasLoader
{
    use \October\Rain\Support\Singleton;

    /**
     * The array of class aliases.
     * @var array
     */
    protected $aliases;

    /**
     * Add an alias to the loader.
     * @param  string  $class
     * @param  string  $alias
     * @return void
     */
    public function alias($class, $alias)
    {
        $this->aliases[$class] = $alias;

        if (method_exists($alias, 'registerSingletonInstance'))
            $alias::registerSingletonInstance();

        if (!class_exists($class)) {
            eval(sprintf('class %s extends %s {}', $class, '\October\Rain\Foundation\AliasBase'));
        }
    }

    public function getAliases()
    {
        return $this->aliases;
    }
}