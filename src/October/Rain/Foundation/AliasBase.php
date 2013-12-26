<?php namespace October\Rain\Foundation;

use App;

/**
 * Alias Base
 *
 * Alias Target classes should use the Singleton trait.
 */
class AliasBase
{
    /**
     * Static call helper
     */
    public static function __callStatic($name, $args)
    {
        $aliasMap = AliasLoader::instance()->getAliases();
        $aliasClass = get_called_class();

        if (!isset($aliasMap[$aliasClass]))
            throw new \Exception('Alias class not registered: '. $aliasClass);

        $targetClass = $aliasMap[$aliasClass];
        $targetObj = new $targetClass;

        if (method_exists($targetObj, $name))
            return forward_static_call_array([$targetObj, $name], $args);

        return forward_static_call_array([$targetObj, '__callStatic'], [$name, $args]);
    }
}