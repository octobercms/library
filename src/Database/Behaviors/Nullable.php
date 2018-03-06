<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Nullable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Nullable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Nullable;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
