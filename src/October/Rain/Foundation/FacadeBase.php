<?php namespace October\Rain\Foundation;

use App;

/**
 * Facade Base
 *
 * Facade Target classes should use the Singleton trait.
 */
class FacadeBase
{
    /**
     * Static call helper
     */
    public static function __callStatic($name, $args)
    {
        $facadeMap = FacadeLoader::instance()->getFacades();
        $facadeClass = get_called_class();

        if (!isset($facadeMap[$facadeClass]))
            throw new \Exception('Facade class not registered: '. $facadeClass);

        $targetClass = $facadeMap[$facadeClass];
        $targetObj = new $targetClass;

        if (method_exists($targetObj, $name))
            return forward_static_call_array([$targetObj, $name], $args);

        return forward_static_call_array([$targetObj, '__callStatic'], [$name, $args]);
    }
}