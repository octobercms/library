<?php namespace October\Rain\Database\Behaviors;

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
}
