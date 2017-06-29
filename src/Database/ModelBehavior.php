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
     * @var \October\Rain\Database\Model Reference to the extended model.
     */
    protected $model;

    /**
     * Constructor
     * @param \October\Rain\Database\Model $model The extended model.
     */
    public function __construct($model)
    {
        $this->model = $model;
    }
}
