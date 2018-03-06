<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Hashable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Hashable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Hashable;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
