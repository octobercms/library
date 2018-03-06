<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * SoftDelete trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class SoftDelete extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\SoftDelete;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
