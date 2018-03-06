<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Revisionable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Revisionable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Revisionable;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
