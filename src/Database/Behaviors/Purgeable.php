<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Purgeable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Purgeable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Purgeable;
}
