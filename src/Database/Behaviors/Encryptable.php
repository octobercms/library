<?php namespace October\Rain\Database;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Encryptable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Encryptable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Encryptable;

    public function __construct($model)
    {
        parent::__construct($model);
    }
}
