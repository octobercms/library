<?php namespace October\Rain\Database;

use October\Rain\Extension\ExtensionBase;

/**
 * Base class for model behaviors.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class ModelBehavior extends ExtensionBase
{

    /**
     * @var Model Reference to the extended model.
     */
    protected $model;

    /**
     * Constructor.
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

}