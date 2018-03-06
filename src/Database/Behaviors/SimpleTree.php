<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * SimpleTree trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class SimpleTree extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\SimpleTree;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
